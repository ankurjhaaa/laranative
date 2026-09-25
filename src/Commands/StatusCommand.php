<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Tymiqly\LaraNative\Database\SQLiteManager;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Platforms\PlatformManager;

/**
 * Shows the current LaraNative project status.
 */
class StatusCommand extends BaseCommand
{
    protected $signature = 'laranative:status';

    protected $description = 'Show the current LaraNative project status';

    public function handle(
        EnvironmentDetector $detector,
        PlatformManager $platformManager,
        SQLiteManager $sqliteManager,
    ): int {
        $this->banner();

        // Project info
        $this->section('Project');
        $this->keyValue('Name', config('laranative.name', 'Not configured'));
        $this->keyValue('ID', config('laranative.id', 'Not configured'));
        $this->keyValue('Version', config('laranative.version', 'Not configured'));

        // Environment
        $this->section('Environment');
        $this->keyValue('OS', $detector->detectOS() . ' (' . php_uname('m') . ')');
        $this->keyValue('PHP', PHP_VERSION);
        $this->keyValue('Laravel', $detector->detectLaravelVersion() ?? 'Unknown');

        // Database
        $this->section('Database');
        $dbPath = $sqliteManager->currentPath();
        $this->keyValue('Connection', config('database.default', 'unknown'));
        $this->keyValue('Path', $dbPath ?? 'Not configured');

        if ($dbPath && file_exists($dbPath)) {
            $size = $sqliteManager->databaseSize();
            $this->keyValue('Size', $size ? $this->formatBytes($size) : 'Unknown');
            $this->keyValue('Tables', (string) $sqliteManager->tableCount());
            $this->keyValue('Migrated', $sqliteManager->isInitialized() ? 'Yes' : 'No');
        }

        // Platforms
        $this->section('Platforms');
        $availability = $platformManager->availability();

        foreach ($availability as $name => $info) {
            $status = $info['available'] ? '<fg=green>available</>' : '<fg=yellow>unavailable</>';
            $this->keyValue($info['platform'], $status);
        }

        // Build output
        $this->section('Build Output');
        $distDir = base_path(config('laranative.build.output_dir', 'dist'));

        if (is_dir($distDir)) {
            $platforms = ['android', 'windows', 'linux', 'macos', 'ios'];
            $hasArtifacts = false;

            foreach ($platforms as $platform) {
                $platformDir = $distDir . DIRECTORY_SEPARATOR . $platform;
                if (is_dir($platformDir)) {
                    $files = glob($platformDir . DIRECTORY_SEPARATOR . '*');
                    if ($files && ! empty($files)) {
                        foreach ($files as $file) {
                            $this->keyValue(
                                ucfirst($platform),
                                basename($file) . ' (' . $this->formatBytes((int) filesize($file)) . ')'
                            );
                            $hasArtifacts = true;
                        }
                    }
                }
            }

            if (! $hasArtifacts) {
                $this->line('  <fg=gray>No build artifacts found</>');
            }
        } else {
            $this->line('  <fg=gray>No dist/ directory</>');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Format bytes to human readable size.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[(int) $factor] ?? 'TB');
    }
}
