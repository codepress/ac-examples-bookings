<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings;

/**
 * Adds a "Edit Columns" link to this plugin's row on the Plugins screen,
 * pointing straight at the Admin Columns column editor for the Hotel Bookings
 * list table.
 *
 * Mirrors AC's own PluginActionLinks, but builds the editor URL as a plain
 * string (consistent with the rest of this example) instead of coupling to AC
 * internal URL types. The editor URL is:
 *
 *   options-general.php?page=codepress-admin-columns&tab=columns&list_screen=<type>
 *
 * where <type> is the Data Sources list screen key — 'acp-data-sources-' plus
 * the data source id registered in CustomListTableInit.
 */
class PluginActionLinks
{

    private const LIST_SCREEN = 'acp-data-sources-hbk_bookings';

    /** @var string */
    private $basename;

    public function __construct(string $file)
    {
        $this->basename = plugin_basename($file);
    }

    public function register(): void
    {
        add_filter('plugin_action_links', [$this, 'add_settings_link'], 10, 2);
        add_filter('network_admin_plugin_action_links', [$this, 'add_settings_link'], 10, 2);
    }

    /**
     * @param string[] $links
     * @param string   $file
     *
     * @return string[]
     */
    public function add_settings_link($links, $file)
    {
        if ($file !== $this->basename) {
            return $links;
        }

        // Only link when the Data Sources addon is active; otherwise the list
        // screen is never registered and the editor would have nothing to show.
        if (! class_exists('ACA\\DataSources\\DataSourceRegistry')) {
            return $links;
        }

        $url = add_query_arg(
            [
                'page'        => 'codepress-admin-columns',
                'tab'         => 'columns',
                'list_screen' => self::LIST_SCREEN,
            ],
            admin_url('options-general.php')
        );

        array_unshift(
            $links,
            sprintf(
                '<a href="%s">%s</a>',
                esc_url($url),
                esc_html__('Edit Columns', 'ac-examples-bookings')
            )
        );

        return $links;
    }
}
