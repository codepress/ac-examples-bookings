<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings\Service;

use ACP\Service\Storage\TemplateFiles;
use SplFileInfo;

/**
 * Registers the bundled column templates (the *.json files in /data) as
 * pre-defined templates that can be loaded from the Admin Columns UI.
 */
class LocalTemplates
{

    private SplFileInfo $dir;

    public function __construct(SplFileInfo $dir)
    {
        $this->dir = $dir;
    }

    public function register(): void
    {
        $dir = $this->dir;

        add_action('acp/ready', static function () use ($dir): void {
            TemplateFiles::from_directory($dir->getRealPath())->register();
        });
    }
}