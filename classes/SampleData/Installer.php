<?php

declare(strict_types=1);

namespace ACA\Examples\Bookings\SampleData;

/**
 * Creates and populates the demo tables (wp_hbk_guests, wp_hbk_rooms,
 * wp_hbk_bookings) from a bundled SQL dump.
 *
 * The dump is only executed when the tables are missing. If all three tables
 * already exist the installer reports "exists" and changes nothing, so the
 * dump's `DROP TABLE` statements never run against populated tables.
 */
class Installer
{

    /** @var string[] */
    private const TABLES = ['wp_hbk_guests', 'wp_hbk_rooms', 'wp_hbk_bookings'];

    /** @var string */
    private $sql_file;

    public function __construct(string $sql_file)
    {
        $this->sql_file = $sql_file;
    }

    /**
     * True only when all three demo tables exist.
     */
    public function is_installed(): bool
    {
        global $wpdb;

        foreach (self::TABLES as $table) {
            $found = $wpdb->get_var(
                $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))
            );

            if ($found !== $table) {
                return false;
            }
        }

        return true;
    }

    /**
     * Row counts per existing table. Only call when is_installed() is true.
     *
     * @return array<string, int>
     */
    public function get_counts(): array
    {
        global $wpdb;

        $counts = [];

        foreach (self::TABLES as $table) {
            $counts[$table] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
        }

        return $counts;
    }

    /**
     * Run the bundled dump unless the tables already exist.
     *
     * @return array{status: string, counts?: array<string, int>, errors?: string[]}
     */
    public function install(): array
    {
        global $wpdb;

        if ($this->is_installed()) {
            return ['status' => 'exists'];
        }

        if (! is_readable($this->sql_file)) {
            return ['status' => 'error', 'errors' => ['Sample data file not found: ' . $this->sql_file]];
        }

        $sql = (string) file_get_contents($this->sql_file);
        $errors = [];

        foreach ($this->split_statements($sql) as $statement) {
            if ($wpdb->query($statement) === false) {
                $errors[] = $wpdb->last_error;
            }
        }

        if ($errors !== []) {
            return ['status' => 'error', 'errors' => $errors];
        }

        return ['status' => 'installed', 'counts' => $this->get_counts()];
    }

    /**
     * Drop the three demo tables.
     *
     * @return array{status: string, errors?: string[]}
     */
    public function uninstall(): array
    {
        global $wpdb;

        $errors = [];

        // Reverse order: bookings references guest/room ids (no FK, but tidy).
        foreach (array_reverse(self::TABLES) as $table) {
            if ($wpdb->query("DROP TABLE IF EXISTS `{$table}`") === false) {
                $errors[] = $wpdb->last_error;
            }
        }

        if ($errors !== []) {
            return ['status' => 'error', 'errors' => $errors];
        }

        return ['status' => 'dropped'];
    }

    /**
     * Split a dump into individual statements.
     *
     * Accumulates physical lines until one ends with a semicolon. This keeps
     * multi-line CREATE blocks and multi-row INSERTs intact, since values in
     * this dump never end a line with `;`. Blank lines and `--` comment lines
     * between statements are skipped.
     *
     * @return string[]
     */
    private function split_statements(string $sql): array
    {
        $statements = [];
        $buffer = '';

        foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
            $trimmed = trim($line);

            if ($buffer === '' && ($trimmed === '' || strpos($trimmed, '--') === 0)) {
                continue;
            }

            $buffer .= $line . "\n";

            if (substr(rtrim($line), -1) === ';') {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }

}