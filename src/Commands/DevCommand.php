<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Tymiqly\LaraNative\Database\SQLiteManager;
use Tymiqly\LaraNative\Runtime\DevRuntime;

/**
 * Start a development server for LaraNative.
 */
class DevCommand extends BaseCommand
{
    protected $signature = 'laranative:dev
        {--host=127.0.0.1 : Server host}
        {--port=8080 : Server port}';

    protected $description = 'Start the LaraNative development server';

    public function handle(SQLiteManager $sqliteManager): int
    {
        $this->banner();

        $host = $this->option('host');
        $port = (int) $this->option('port');

        // Configure SQLite for dev
        $sqliteManager->configure();

        try {
            $dbPath = $sqliteManager->ensureDatabase();
            $this->check("SQLite database: {$dbPath}");
        } catch (\Throwable $e) {
            $this->failure("Database error: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Run migrations if needed
        if (! $sqliteManager->isInitialized()) {
            $this->line('  Running initial migrations...');
            $result = $sqliteManager->runMigrations();

            if ($result['status'] === 'success') {
                $this->check('Migrations completed');
            } else {
                $this->warning("Migration issue: {$result['output']}");
            }
        }

        $this->newLine();
        $this->line('<fg=green;options=bold>LaraNative Development Server</>');
        $this->newLine();
        $this->line("  Server:   <fg=cyan>http://{$host}:{$port}</>");
        $this->line('  Database: <fg=cyan>SQLite</>');
        $this->line('  Mode:     <fg=cyan>Development</>');
        $this->newLine();
        $this->line('  Press <fg=yellow>Ctrl+C</> to stop.');
        $this->newLine();

        // Start the PHP built-in server (this blocks)
        $publicPath = base_path('public');
        $router = base_path('public' . DIRECTORY_SEPARATOR . 'index.php');

        $command = sprintf(
            '%s -S %s:%d -t %s %s',
            PHP_BINARY,
            $host,
            $port,
            escapeshellarg($publicPath),
            escapeshellarg($router)
        );

        passthru($command, $exitCode);

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
