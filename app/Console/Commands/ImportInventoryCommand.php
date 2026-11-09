<?php

namespace App\Console\Commands;

use App\Actions\Inventory\InventoryImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonException;

#[Signature('inventory:import {file : Path to the inventory JSON file} {--apply : Persist verified records} {--report= : Write a JSON report to this path}')]
#[Description('Validate and import a Librarium inventory JSON document')]
class ImportInventoryCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InventoryImporter $importer): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Inventory file [$path] does not exist or is not readable.");

            return self::FAILURE;
        }

        $reportPath = $this->option('report');

        if ($reportPath !== null && ! $this->canWriteReportTo((string) $reportPath)) {
            $this->error("Report path [$reportPath] is not writable.");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->error("Unable to read inventory file [$path].");

            return self::FAILURE;
        }

        try {
            $document = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error("Inventory file is not valid JSON: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if (! is_array($document) || array_is_list($document)) {
            $this->error('Inventory JSON must contain a top-level object.');

            return self::FAILURE;
        }

        $report = $importer->run($document, (bool) $this->option('apply'));

        if ($report['status'] === 'invalid' || $report['status'] === 'blocked') {
            foreach ($report['errors'] as $path => $messages) {
                foreach ($messages as $message) {
                    $this->error("$path: $message");
                }
            }

            return self::FAILURE;
        }

        $this->info($report['status'] === 'applied' ? 'Inventory import applied.' : 'Inventory dry run completed; no database changes were made.');
        $rows = [];

        foreach ($report['summary'] as $type => $counts) {
            $rows[] = [
                $type,
                $counts['create'] ?? 0,
                $counts['update'] ?? 0,
                $counts['unchanged'] ?? 0,
                $counts['additive'] ?? 0,
                $counts['skipped'] ?? 0,
                $counts['ambiguous'] ?? 0,
                $counts['conflict'] ?? 0,
            ];
        }

        $this->table(
            ['Records', 'Created', 'Updated', 'Unchanged', 'Added', 'Skipped', 'Ambiguous', 'Conflicts'],
            $rows,
        );

        foreach ($report['notes'] as $note) {
            $this->comment($note);
        }

        if ($reportPath !== null) {
            $encodedReport = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $bytesWritten = file_put_contents((string) $reportPath, $encodedReport.PHP_EOL, LOCK_EX);

            if ($bytesWritten === false) {
                $this->error("Unable to write report to [$reportPath].");

                return self::FAILURE;
            }

            $this->line("Report written to [$reportPath].");
        }

        return self::SUCCESS;
    }

    private function canWriteReportTo(string $path): bool
    {
        if (is_dir($path)) {
            return false;
        }

        $directory = dirname($path);

        return is_dir($directory) && is_writable($directory) && (! file_exists($path) || is_writable($path));
    }
}
