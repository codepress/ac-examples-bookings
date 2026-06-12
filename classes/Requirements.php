<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings;

/**
 * Surfaces an admin notice when the plugin's runtime prerequisites are missing.
 *
 * The Hotel Bookings list table is registered through the Data Sources addon
 * of Admin Columns Pro. Without it the registration hook never fires, so this
 * notice tells the user why the table does not appear. The sample-data tools
 * page keeps working regardless.
 */
class Requirements
{

    public function register(): void
    {
        add_action('admin_notices', [$this, 'maybe_render_notice']);
    }

    public function is_met(): bool
    {
        // The registry class only exists when Admin Columns Pro is active with
        // the Data Sources addon — the actual capability this example needs.
        // Detecting the class (rather than comparing ACP_VERSION) avoids false
        // negatives on pre-release builds such as "7.1beta".
        return class_exists('ACA\\DataSources\\DataSourceRegistry');
    }

    public function maybe_render_notice(): void
    {
        if ($this->is_met() || ! current_user_can('activate_plugins')) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html__(
                'ACP Sample Data – Hotel Bookings needs Admin Columns Pro 7.1 or newer with the Data Sources addon active. The Hotel Bookings list table will not appear until both are enabled.',
                'ac-examples-bookings'
            )
        );
    }

}