<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * Prepares the SQLite database for the build.
 *
 * Creates an empty SQLite database file in the build directory.
 * The actual migrations run at first launch on the device.
 */
class PrepareDatabaseStep extends BuildStep
{
    public function name(): string
    {
        return 'Prepare SQLite database';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        $dbDir = $context->buildPath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'database';

        if (! is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $filename = $context->config('database.filename', 'database.sqlite');
        $dbPath = $dbDir . DIRECTORY_SEPARATOR . $filename;

        // Create empty SQLite database
        if (file_put_contents($dbPath, '') === false) {
            $output("  ✗ Failed to create database at: {$dbPath}");

            return false;
        }

        // Set up .env for SQLite in the build
        $envPath = $context->buildPath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . '.env';
        $this->configureSqliteEnv($envPath, $filename);

        $output('  ✓ SQLite database prepared');

        return true;
    }

    /**
     * Modify or create .env to configure SQLite.
     */
    protected function configureSqliteEnv(string $envPath, string $filename): void
    {
        $envContent = '';

        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath) ?: '';

            // Remove existing DB settings
            $envContent = preg_replace('/^DB_CONNECTION=.*$/m', '', $envContent);
            $envContent = preg_replace('/^DB_HOST=.*$/m', '', $envContent);
            $envContent = preg_replace('/^DB_PORT=.*$/m', '', $envContent);
            $envContent = preg_replace('/^DB_DATABASE=.*$/m', '', $envContent);
            $envContent = preg_replace('/^DB_USERNAME=.*$/m', '', $envContent);
            $envContent = preg_replace('/^DB_PASSWORD=.*$/m', '', $envContent);

            // Clean up multiple blank lines
            $envContent = preg_replace('/\n{3,}/', "\n\n", $envContent);
        }

        // Add SQLite configuration
        $envContent = trim($envContent) . "\n\n# LaraNative SQLite Configuration\n";
        $envContent .= "DB_CONNECTION=sqlite\n";
        $envContent .= "DB_DATABASE=database/{$filename}\n";

        file_put_contents($envPath, $envContent);
    }
}
