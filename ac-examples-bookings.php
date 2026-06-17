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

(new Requirements())->register();

new CustomListTableInit();

(new AdminPage(
    new Installer(__DIR__ . '/data/sample-data.sql')
))->register();

// Register the bundled column templates (data/*.json) as pre-defined templates.
(new LocalTemplates(new SplFileInfo(__DIR__ . '/data')))->register();

// Add an "Edit Columns" link to the plugin row on the Plugins screen.
(new PluginActionLinks(AC_EXAMPLES_BOOKINGS_FILE))->register();