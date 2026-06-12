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

define('AC_EXAMPLES_BOOKINGS_FILE', __FILE__);

$autoload = __DIR__ . '/vendor/autoload.php';

if (! is_readable($autoload)) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'ACP Sample Data – Hotel Bookings is missing its Composer dependencies. Run "composer install" in the plugin directory.',
                'ac-examples-bookings'
            )
        );
    });

    return;
}

require $autoload;

(new Requirements())->register();

new CustomListTableInit();

(new AdminPage(
    new Installer(__DIR__ . '/data/sample-data.sql')
))->register();