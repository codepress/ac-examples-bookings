<?php

declare(strict_types=1);

/**
 * Removes the demo tables when the plugin is deleted from WordPress.
 *
 * Self-contained on purpose: uninstall.php runs without bootstrapping the
 * plugin, so none of the plugin's classes are loaded here. The table list
 * mirrors ACA\Examples\Bookings\SampleData\Installer.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$tables = ['wp_hbk_bookings', 'wp_hbk_guests', 'wp_hbk_rooms'];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

// Mirrors ImportTemplates::IMPORTED_OPTION — clearing it lets a reinstall
// re-import the bundled templates.
//
// The imported views themselves live in wp_admin_columns and are left
// untouched here, so a reinstall that follows will re-import on top of them and
// duplicate the views. That's an accepted trade-off for this example plugin:
// uninstall keeps the user's saved views rather than deleting their data.
delete_option('aca_examples_bookings_templates_imported');