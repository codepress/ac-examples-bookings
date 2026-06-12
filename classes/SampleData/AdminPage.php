<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings\SampleData;

/**
 * Adds a "Hotel Bookings Sample Data" page under the Tools menu with a single
 * button that creates and populates the demo tables via the Installer.
 */
class AdminPage
{

    private const ACTION = 'acx_bookings_install';

    private const ACTION_DROP = 'acx_bookings_drop';

    private const MENU_SLUG = 'acx-bookings-sample-data';

    private const TRANSIENT = 'acx_bookings_install_result';

    /**
     * Admin page slug of the registered Hotel Bookings list table.
     *
     * The Data Sources addon builds it as Addon::slug('hbk_bookings'), i.e.
     * 'acp-data-sources-' + the data source id used in CustomListTableInit.
     */
    private const LIST_TABLE_PAGE = 'acp-data-sources-hbk_bookings';

    /** @var Installer */
    private $installer;

    public function __construct(Installer $installer)
    {
        $this->installer = $installer;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_page']);
        add_action('admin_post_' . self::ACTION, [$this, 'handle_install']);
        add_action('admin_post_' . self::ACTION_DROP, [$this, 'handle_drop']);
    }

    public function add_page(): void
    {
        add_management_page(
            'Hotel Bookings Sample Data',
            'Hotel Bookings Sample Data',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_page']
        );
    }

    /**
     * Handle the form submission, then redirect back to the page (PRG).
     */
    public function handle_install(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'ac-examples-bookings'));
        }

        check_admin_referer(self::ACTION);

        $result = $this->installer->install();

        set_transient(self::TRANSIENT . '_' . get_current_user_id(), $result, MINUTE_IN_SECONDS);

        wp_safe_redirect(admin_url('tools.php?page=' . self::MENU_SLUG));
        exit;
    }

    /**
     * Handle the "drop tables" submission, then redirect back to the page (PRG).
     */
    public function handle_drop(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'ac-examples-bookings'));
        }

        check_admin_referer(self::ACTION_DROP);

        $result = $this->installer->uninstall();

        set_transient(self::TRANSIENT . '_' . get_current_user_id(), $result, MINUTE_IN_SECONDS);

        wp_safe_redirect(admin_url('tools.php?page=' . self::MENU_SLUG));
        exit;
    }

    public function render_page(): void
    {
        $installed = $this->installer->is_installed();
        $result = $this->pull_result();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Hotel Bookings Sample Data', 'ac-examples-bookings') . '</h1>';

        if ($result !== false) {
            $this->render_notice($result);
        }

        echo '<p>' . esc_html__(
            'Create the demo tables (guests, rooms and bookings) and load the sample dataset used by the Hotel Bookings custom list table.',
            'ac-examples-bookings'
        ) . '</p>';

        if ($installed) {
            $this->render_status($this->installer->get_counts());

            $link = $this->list_table_link();

            if ($link !== '') {
                echo '<p>' . $link . '</p>';
            }

            echo '<p>' . esc_html__(
                'The sample tables already exist. Drop them below to reset; you can then re-create and re-populate them.',
                'ac-examples-bookings'
            ) . '</p>';

            $confirm = esc_js(__('This permanently drops the wp_hbk_guests, wp_hbk_rooms and wp_hbk_bookings tables. Continue?', 'ac-examples-bookings'));

            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'' . $confirm . '\');">';
            echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION_DROP) . '">';
            wp_nonce_field(self::ACTION_DROP);
            submit_button(__('Drop tables (reset)', 'ac-examples-bookings'), 'delete');
            echo '</form>';

            echo '</div>';

            return;
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '">';
        wp_nonce_field(self::ACTION);
        submit_button(__('Create & populate sample tables', 'ac-examples-bookings'));
        echo '</form>';

        echo '</div>';
    }

    /**
     * Read and clear the one-shot result stored by handle_install().
     *
     * @return array<string, mixed>|false
     */
    private function pull_result()
    {
        $key = self::TRANSIENT . '_' . get_current_user_id();
        $result = get_transient($key);

        if ($result === false) {
            return false;
        }

        delete_transient($key);

        return is_array($result) ? $result : false;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function render_notice(array $result): void
    {
        $status = isset($result['status']) ? (string) $result['status'] : '';

        if ($status === 'installed') {
            $link = $this->list_table_link();

            printf(
                '<div class="notice notice-success is-dismissible"><p>%s%s</p></div>',
                esc_html__('Sample tables created and populated.', 'ac-examples-bookings'),
                $link === '' ? '' : ' ' . $link
            );

            return;
        }

        if ($status === 'dropped') {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html__('Sample tables dropped.', 'ac-examples-bookings')
            );

            return;
        }

        if ($status === 'exists') {
            printf(
                '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                esc_html__('The sample tables already exist. Nothing was changed.', 'ac-examples-bookings')
            );

            return;
        }

        $errors = isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : [];

        printf(
            '<div class="notice notice-error is-dismissible"><p>%s</p>%s</div>',
            esc_html__('Could not install the sample data.', 'ac-examples-bookings'),
            $errors === []
                ? ''
                : '<pre>' . esc_html(implode("\n", array_map('strval', $errors))) . '</pre>'
        );
    }

    /**
     * Link to the registered Hotel Bookings list table.
     *
     * Returns an empty string when the Data Sources addon is inactive, so the
     * page never offers a dead link to a screen that is not registered.
     */
    private function list_table_link(): string
    {
        if (! class_exists('ACA\\DataSources\\DataSourceRegistry')) {
            return '';
        }

        return sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=' . self::LIST_TABLE_PAGE)),
            esc_html__('View the Hotel Bookings table →', 'ac-examples-bookings')
        );
    }

    /**
     * @param array<string, int> $counts
     */
    private function render_status(array $counts): void
    {
        echo '<table class="widefat striped" style="max-width:420px">';
        echo '<thead><tr><th>' . esc_html__('Table', 'ac-examples-bookings') . '</th><th>' . esc_html__('Rows', 'ac-examples-bookings') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($counts as $table => $count) {
            printf(
                '<tr><td><code>%s</code></td><td>%s</td></tr>',
                esc_html($table),
                esc_html((string) $count)
            );
        }

        echo '</tbody></table>';
    }

}