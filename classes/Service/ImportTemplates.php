<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings\Service;

use AC;
use AC\ListScreenRepository\Storage;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use ACP\ListScreenFactory;
use ACP\ListScreenRepository\TemplateJsonFile;
use SplFileInfo;

/**
 * Imports the bundled column templates (the *.json files in /data) as real,
 * saved Admin Columns views on first run, so the Hotel Bookings screen is fully
 * configured out of the box — no manual "load template" step.
 *
 * This is the programmatic equivalent of clicking "Import" in the template
 * picker. It uses the same API as ACP's import handler
 * (ACP\RequestHandler\Ajax\ListScreenImportTemplate):
 *
 *   1. find the template by id in the read-only template repository,
 *   2. duplicate() it so it gets a fresh id (and segment/rule keys),
 *   3. save() the copy to writable storage (the wp_admin_columns table).
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

        $templates = $container->get(TemplateJsonFile::class);
        $factory = $container->get(ListScreenFactory::class);
        $storage = $container->get(Storage::class);

        $ids = $this->get_template_ids();
        $imported = 0;

        foreach ($ids as $id) {
            $template = $templates->find($id);

            if (! $template) {
                continue;
            }

            $list_screen = $factory->duplicate($template);
            $list_screen->set_status(ListScreenStatus::create_active());

            $storage->save($list_screen);
            $imported++;
        }

        // Only mark done once every bundled template imported. A template can
        // only be decoded once its data source resolves, which needs the demo
        // tables (wp_hbk_*) to exist — so before "Create & populate sample
        // tables" has run, find() returns null. Leaving the flag unset lets a
        // later request (e.g. the redirect right after the tables are created)
        // retry and succeed, with no user action.
        if ($ids && $imported === count($ids)) {
            update_option(self::IMPORTED_OPTION, true, false);
        }
    }

    /**
     * The list-screen ids embedded in our bundled data/*.json files.
     *
     * We look up our templates by id rather than calling the repository's
     * find_all(): that repository reads from a site-wide filter
     * (acp/storage/template/files) which other plugins may also feed, so
     * scoping to our own ids keeps the import to exactly these two views.
     *
     * @return ListScreenId[]
     */
    private function get_template_ids(): array
    {
        $ids = [];

        $files = glob(rtrim((string)$this->dir->getRealPath(), '/') . '/*.json') ?: [];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $data = json_decode($contents, true);

            foreach ((array)$data as $screen) {
                $id = $screen['list_screen']['id'] ?? null;

                if (is_string($id) && $id !== '') {
                    $ids[] = new ListScreenId($id);
                }
            }
        }

        return $ids;
    }

}