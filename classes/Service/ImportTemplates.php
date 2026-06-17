<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings\Service;

use AC;
use AC\Type\ListScreenStatus;
use ACP\Import\ImportOptions;
use ACP\Import\ListScreenImporter;
use RuntimeException;
use SplFileInfo;

/**
 * Imports the bundled column templates (the *.json files in /data) as real,
 * saved Admin Columns views on first run, so the Hotel Bookings screen is fully
 * configured out of the box — no manual "load template" step.
 *
 * This is the programmatic equivalent of clicking "Import" in the template
 * picker. It delegates to ACP's public import API (ACP\Import\ListScreenImporter),
 * which reads the *.json files in /data and, for each, duplicates the view so it
 * gets a fresh id (and segment/rule keys) and saves the copy to writable storage
 * (the wp_admin_columns table). The active status overwrite makes the views
 * visible immediately.
 *
 * Runs once, guarded by an option flag, so it does not re-import on every
 * request — a user can freely edit or delete the imported views afterwards
 * without them reappearing.
 */
class ImportTemplates
{

    private const IMPORTED_OPTION = 'aca_examples_bookings_templates_imported';

    private SplFileInfo $dir;

    public function __construct(SplFileInfo $dir)
    {
        $this->dir = $dir;
    }

    public function register(): void
    {
        // acp/init (after_setup_theme:2) hands us the same DI container ACP uses
        // to build its own import handler. We can't import here, though — it
        // runs before the admin environment is ready. So we grab the container
        // and defer the actual work to admin_init.
        add_action('acp/init', [$this, 'configure']);
    }

    public function configure(AC\DI\Container $container): void
    {
        // Run on admin_init, not init: decoding a data-source view builds its
        // list table (a WP_List_Table subclass), which needs the admin screen
        // environment (WP_List_Table, convert_to_screen(), the current screen).
        // That isn't loaded during `init`, but is by admin_init — the same
        // context ACP's own AJAX import handler runs in.
        add_action('admin_init', function () use ($container): void {
            $this->import($container);
        });
    }

    private function import(AC\DI\Container $container): void
    {
        if (get_option(self::IMPORTED_OPTION)) {
            return;
        }

        $importer = $container->get(ListScreenImporter::class);

        try {
            $imported = $importer->from_directory(
                (string)$this->dir->getRealPath(),
                ImportOptions::create()->with_status(ListScreenStatus::create_active())
            );
        } catch (RuntimeException $e) {
            // Unreadable path or a malformed file: leave the flag unset so a
            // later request retries, rather than fataling admin_init.
            return;
        }

        // Only mark done once at least one template imported. A template can
        // only be decoded once its data source resolves, which needs the demo
        // tables (wp_hbk_*) to exist — before "Create & populate sample tables"
        // has run, the importer skips it (has_list_screen() returns false) and
        // the returned collection is empty. Leaving the flag unset lets a later
        // request (e.g. the redirect right after the tables are created) retry
        // and succeed, with no user action.
        if ($imported->count() > 0) {
            update_option(self::IMPORTED_OPTION, true, false);
        }
    }

}