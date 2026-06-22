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

$installer = new Installer(__DIR__ . '/data/sample-data.sql');

(new AdminPage($installer))->register();

// (Re)create and populate the demo tables when the plugin is activated, so a
// deactivate/reactivate cycle restores the sample data automatically. install()
// is a no-op when the tables already exist, so a first-time activation that
// happens before the data source is registered stays safe too.
register_activation_hook(AC_EXAMPLES_BOOKINGS_FILE, static function () use ($installer): void {
    $installer->install();
});

// Drop the demo tables when the plugin is deactivated. This is demo data, so it
// is safe to remove on deactivate; deleting the plugin runs uninstall.php, which
// performs the same cleanup.
//
// The imported column views and their imported-templates flag are left in place:
// reactivation recreates the tables under the same names, so the existing views
// keep working. Clearing the flag here would instead make ImportTemplates
// re-import on the next load, duplicating those views.
register_deactivation_hook(AC_EXAMPLES_BOOKINGS_FILE, static function () use ($installer): void {
    $installer->uninstall();
});

// Register the bundled column templates (data/*.json) as pre-defined templates.
(new LocalTemplates(new SplFileInfo(__DIR__ . '/data')))->register();

// Import those templates as saved views on first run, so the screen is ready
// to use without manually loading a template.
(new ImportTemplates(new SplFileInfo(__DIR__ . '/data')))->register();

// Add an "Edit Columns" link to the plugin row on the Plugins screen.
(new PluginActionLinks(AC_EXAMPLES_BOOKINGS_FILE))->register();