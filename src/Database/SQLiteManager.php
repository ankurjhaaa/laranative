<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tymiqly\LaraNative\Exceptions\LaraNativeException;

/**
 * Manages SQLite database creation, configuration, and migration for native builds.
 */
class SQLiteManager
{
    public function __construct(
        protected DatabasePathResolver $pathResolver
    ) {}

    /**
     * Configure the SQLite connection for LaraNative.
     *
     * This modifies the runtime database configuration to use SQLite
     * at the platform-appropriate path.
     */
    public function configure(?string $platform = null): void
    {
        $dbPath = $platform
            ? $this->pathResolver->resolve($platform)
            : $this->pathResolver->resolveForDev();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $dbPath);

        if (config('laranative.database.wal_mode', true)) {
            Config::set('database.connections.sqlite.options', [
                \PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        }
    }

    /**
     * Ensure the SQLite database file and its parent directory exist.
     */
    public function ensureDatabase(?string $path = null): string
    {
        $dbPath = $path ?? $this->pathResolver->resolveForDev();

        $directory = dirname($dbPath);

        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new LaraNativeException(
                    "Failed to create database directory: {$directory}"
                );
            }
        }

        if (! file_exists($dbPath)) {
            if (file_put_contents($dbPath, '') === false) {
                throw new LaraNativeException(
                    "Failed to create database file: {$dbPath}"
                );
            }
        }

        // Enable WAL mode for better concurrent access
        if (config('laranative.database.wal_mode', true)) {
            $this->enableWalMode($dbPath);
        }

        return $dbPath;
    }

    /**
     * Enable WAL (Write-Ahead Logging) mode on the SQLite database.
     */
    protected function enableWalMode(string $dbPath): void
    {
        try {
            $pdo = new \PDO("sqlite:{$dbPath}");
            $pdo->exec('PRAGMA journal_mode=WAL;');
        } catch (\PDOException $e) {
            // WAL mode is a performance optimisation, not critical
        }
    }

    /**
     * Run Laravel migrations against the configured SQLite database.
     *
     * @return array{status: string, output: string}
     */
    public function runMigrations(): array
    {
        try {
            $exitCode = Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return [
                'status' => $exitCode === 0 ? 'success' : 'failed',
                'output' => trim($output),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the database is initialised (has at least the migrations table).
     */
    public function isInitialized(): bool
    {
        try {
            return Schema::hasTable('migrations');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get the current database path.
     */
    public function currentPath(): ?string
    {
        return config('database.connections.sqlite.database');
    }

    /**
     * Get database file size in bytes.
     */
    public function databaseSize(): ?int
    {
        $path = $this->currentPath();

        if ($path && file_exists($path)) {
            return (int) filesize($path);
        }

        return null;
    }

    /**
     * Get the number of tables in the database.
     */
    public function tableCount(): int
    {
        try {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            return count($tables);
        } catch (\Throwable) {
            return 0;
        }
    }
}
