<?php
/*
Plugin Name: ACP Sample Data - Hotel Bookings
Version: 1.0
Description: Example data for Hotel Booking
Author: AdminColumns.com
Author URI: https://www.admincolumns.com
Plugin URI: https://www.admincolumns.com
Requires PHP: 7.4
*/

namespace ACA\Examples\Bookings;

use ACA\Examples\Bookings\SampleData\AdminPage;
use ACA\Examples\Bookings\SampleData\Installer;
use ACA\Examples\Bookings\Service\ImportTemplates;
use ACA\Examples\Bookings\Service\LocalTemplates;
use SplFileInfo;

define('AC_EXAMPLES_BOOKINGS_FILE', __FILE__);

// All classes live in the ACA\Examples\Bookings\ namespace, so we load them
// with plain requires rather than an autoloader. Nothing to install.
require __DIR__ . '/classes/Requirements.php';
require __DIR__ . '/classes/CustomListTableInit.php';
require __DIR__ . '/classes/PluginActionLinks.php';
require __DIR__ . '/classes/SampleData/Installer.php';
require __DIR__ . '/classes/SampleData/AdminPage.php';
require __DIR__ . '/classes/Service/LocalTemplates.php';
require __DIR__ . '/classes/Service/ImportTemplates.php';

(new Requirements())->register();

new CustomListTableInit();

(new AdminPage(
    new Installer(__DIR__ . '/data/sample-data.sql')
))->register();

// Drop the demo tables and clear the imported-templates flag when the plugin
// is deactivated. This is demo data, so it is safe to remove on deactivate;
// deleting the plugin runs uninstall.php, which performs the same cleanup.
register_deactivation_hook(AC_EXAMPLES_BOOKINGS_FILE, static function (): void {
    (new Installer(__DIR__ . '/data/sample-data.sql'))->uninstall();

    // Mirrors ImportTemplates::IMPORTED_OPTION — clearing it lets a reinstall
    // re-import the bundled templates.
    delete_option('aca_examples_bookings_templates_imported');
});

// Register the bundled column templates (data/*.json) as pre-defined templates.
(new LocalTemplates(new SplFileInfo(__DIR__ . '/data')))->register();

// Import those templates as saved views on first run, so the screen is ready
// to use without manually loading a template.
(new ImportTemplates(new SplFileInfo(__DIR__ . '/data')))->register();

// Add an "Edit Columns" link to the plugin row on the Plugins screen.
(new PluginActionLinks(AC_EXAMPLES_BOOKINGS_FILE))->register();