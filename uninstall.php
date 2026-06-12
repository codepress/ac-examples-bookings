<?php

declare(strict_types=1);

/**
 * Removes the demo tables when the plugin is deleted from WordPress.
 *
 * Self-contained on purpose: uninstall.php must run without the Composer
 * autoloader (vendor/ may be absent at delete time). The table list mirrors
 * ACA\Examples\Bookings\SampleData\Installer.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$tables = ['wp_hbk_bookings', 'wp_hbk_guests', 'wp_hbk_rooms'];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}