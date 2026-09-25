<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Tymiqly\LaraNative\Database\SQLiteManager;
use Tymiqly\LaraNative\Storage\LaraNativeStorage;

/**
 * Sets up the LaraNative development environment.
 *
 * Configures SQLite, creates storage directories, and runs initial migrations.
 */
class SetupCommand extends BaseCommand
{
    protected $signature = 'laranative:setup';

    protected $description = 'Set up the LaraNative development environment';

    public function handle(SQLiteManager $sqliteManager, LaraNativeStorage $storage): int
    {
        $this->banner();
        $this->line('Setting up LaraNative...');
        $this->newLine();

        // 1. Check config exists
        if (! file_exists(config_path('laranative.php'))) {
            $this->failure('config/laranative.php not found. Run: php artisan laranative:install');

            return self::FAILURE;
        }

        $this->check('Configuration found');

        // 2. Configure SQLite
        $sqliteManager->configure();
        $this->check('SQLite connection configured');

        // 3. Create database
        try {
            $dbPath = $sqliteManager->ensureDatabase();
            $this->check("Database created at: {$dbPath}");
        } catch (\Throwable $e) {
            $this->failure("Database creation failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        // 4. Create storage directories
        try {
            $storage->ensureDirectories();
            $this->check('Storage directories created');
        } catch (\Throwable $e) {
            $this->warning("Some storage directories could not be created: {$e->getMessage()}");
        }

        // 5. Run migrations
        if (config('laranative.database.auto_migrate', true)) {
            $this->newLine();
            $this->line('Running migrations...');

            $result = $sqliteManager->runMigrations();

            if ($result['status'] === 'success') {
                $this->check('Migrations completed');
            } else {
                $this->warning("Migration status: {$result['status']}");
                if ($result['output']) {
                    $this->line("  {$result['output']}");
                }
            }
        }

        $this->newLine();
        $this->line('<fg=green;options=bold>LaraNative setup complete!</>');
        $this->newLine();
        $this->line('You can now run:');
        $this->line('  <fg=cyan>php artisan laranative:dev</> — start development server');
        $this->line('  <fg=cyan>php artisan laranative:doctor</> — check environment');
        $this->line('  <fg=cyan>php artisan laranative:build android</> — build for Android');
        $this->newLine();

        return self::SUCCESS;
    }
}
