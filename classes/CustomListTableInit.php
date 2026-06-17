<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings;

use ACA\DataSources\DataSource;
use ACA\DataSources\DataSource\ColumnLabelResolver\HumanReadableResolver;
use ACA\DataSources\DataSource\ColumnType;
use ACA\DataSources\DataSource\Config;
use ACA\DataSources\DataSourceRegistry;
use ACA\DataSources\DataSourceRegistry\Entry;
use ACA\DataSources\Facade;
use ACA\DataSources\Type\DataSourceId;

/**
 * Registers the wp_hbk_bookings demo table (plus its guest/room lookups) as an
 * Admin Columns Pro Custom List Table.
 *
 * Requires the Data Sources addon (Admin Columns Pro 7.1+).
 *
 * WHAT THIS CLASS DOES (the programmatic part):
 *   - Registers wp_hbk_bookings as a Custom List Table under its own admin menu.
 *   - Registers wp_hbk_guests and wp_hbk_rooms as related Data Sources so the
 *     Guest and Room columns can resolve IDs to human labels.
 *   - Types the columns so sensible defaults appear: Unix-timestamp dates,
 *     status / payment as labelled selects, amounts as numbers, email as email.
 *
 * WHAT IS DONE IN THE ADMIN COLUMNS UI AFTERWARDS (the view, stored in
 * wp_admin_columns — not configurable from this hook):
 *   - Column order and which columns are shown.
 *   - Guest column  -> set "Column" to "Guest" (full_name); Room -> "Room type".
 *   - Total / Paid  -> Display as Currency, EUR (€1,234.00).
 *   - Check-in/out  -> Date display format "j M Y" (e.g. 18 Jun 2026).
 *   - Status        -> Conditional Formatting colour pills
 *                      (Pending amber, Confirmed green, Cancelled red, Completed blue).
 *   - Footer Metrics: Total = Sum of Total, Avg booking = Average of Total,
 *                     Bookings = Count of Ref.
 *   - Smart Filters: Status, Source, Check-in date range.
 */
class CustomListTableInit
{

    public function __construct()
    {
        add_action('acp/data-sources/register', [$this, 'register']);
    }

    public function register(DataSourceRegistry $registry): void
    {
        // Bail out if the demo tables haven't been imported (or were removed).
        // Registering a Data Source for a missing table makes the addon throw
        // a TableNotReadableException, surfacing a "Table ... is not readable"
        // admin notice. Checking up front keeps the screen clean.
        if (! $this->tables_exist(['wp_hbk_guests', 'wp_hbk_rooms', 'wp_hbk_bookings'])) {
            return;
        }

        // ------------------------------------------------------------------ //
        // Guests lookup — resolves bookings.guest_id -> guest full name.     //
        // The table identifier is set to `full_name` (a generated column),   //
        // so the Guest relation column shows "Mia van Dijk" by default.      //
        // Registered without a menu: it only feeds the relation below.       //
        // ------------------------------------------------------------------ //
        $guests = new DataSource(
            new DataSourceId('hbk_guests'),
            Facade\Table::from('wp_hbk_guests', 'full_name'),
            Config\Columns::create()
                ->with_columns([
                    ColumnType\EmailType::for('email')->with_label('Email'),
                ])
                ->with_label_resolver(new HumanReadableResolver())
        );

        $registry->register(new Entry($guests));

        // ------------------------------------------------------------------ //
        // Rooms lookup — resolves bookings.room_id -> room type.             //
        // Identifier set to `room_type` so the Room relation column shows     //
        // "Deluxe Room" by default.                                          //
        // ------------------------------------------------------------------ //
        $rooms = new DataSource(
            new DataSourceId('hbk_rooms'),
            Facade\Table::from('wp_hbk_rooms', 'room_type'),
            Config\Columns::create()
                ->with_columns([
                    ColumnType\NumberType::for('rate')->with_label('Nightly rate'),
                    ColumnType\NumberType::for('capacity')->with_label('Capacity'),
                    ColumnType\BooleanType::for('active')->with_label('Active'),
                ])
                ->with_label_resolver(new HumanReadableResolver())
        );

        $registry->register(new Entry($rooms));

        // ------------------------------------------------------------------ //
        // Bookings — the hero table. Identifier stays the primary key (id).  //
        // ------------------------------------------------------------------ //
        $bookings_columns = Config\Columns::create()
            ->with_columns([
                ColumnType\TextType::for('reference')->with_label('Ref.'),

                // check_in / check_out are stored as raw Unix timestamps ('U').
                ColumnType\DateTimeType::for('check_in', 'U')->with_label('Check-in'),
                ColumnType\DateTimeType::for('check_out', 'U')->with_label('Check-out'),

                ColumnType\NumberType::for('nights')->with_label('Nights'),
                ColumnType\NumberType::for('guests_count')->with_label('Pax'),
                ColumnType\NumberType::for('total_amount')->with_label('Total'),
                ColumnType\NumberType::for('amount_paid')->with_label('Paid'),

                // Integer codes mapped to readable labels.
                ColumnType\SelectColumnType::for('status', [
                    0 => 'Pending',
                    1 => 'Confirmed',
                    2 => 'Cancelled',
                    3 => 'Completed',
                ])->with_label('Status'),
                ColumnType\SelectColumnType::for('payment_status', [
                    0 => 'Unpaid',
                    1 => 'Partial',
                    2 => 'Paid',
                ])->with_label('Payment'),

                ColumnType\TextType::for('source')->with_label('Source'),
            ])
            ->with_label_resolver(new HumanReadableResolver());

        $bookings = new DataSource(
            new DataSourceId('hbk_bookings'),
            Facade\Table::from('wp_hbk_bookings'),
            $bookings_columns,
            new Facade\Relations([
                // One guest per booking (bookings.guest_id -> guests.id).
                Facade\Relation\Column::has_one($guests, 'id', 'Guest', 'guest_id'),

                // One room per booking (bookings.room_id -> rooms.id).
                Facade\Relation\Column::has_one($rooms, 'id', 'Room', 'room_id'),
            ])
        );

        $registry->register(
            Entry::create($bookings)
                ->set_menu('Hotel Bookings', 'Hotel Bookings', 'dashicons-calendar-alt', 25)
        );
    }

    /**
     * @param string[] $tables Full table names (including prefix).
     */
    private function tables_exist(array $tables): bool
    {
        global $wpdb;

        foreach ($tables as $table) {
            $found = $wpdb->get_var(
                $wpdb->prepare('SHOW TABLES LIKE %s', $table)
            );

            if ($found !== $table) {
                return false;
            }
        }

        return true;
    }

}