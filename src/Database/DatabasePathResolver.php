<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Database;

use Illuminate\Support\Facades\Config;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;

/**
 * Resolves the SQLite database path dynamically based on the target platform.
 *
 * Never hardcodes a filesystem path; each platform resolves its own data directory.
 */
class DatabasePathResolver
{
    public function __construct(
        protected EnvironmentDetector $detector
    ) {}

    /**
     * Resolve the absolute path to the SQLite database file.
     *
     * @param  string|null  $platform  Force a specific platform, or detect automatically.
     */
    public function resolve(?string $platform = null): string
    {
        $platform = $platform ?? $this->detector->detectOS();
        $filename = config('laranative.database.filename', 'database.sqlite');

        $baseDir = $this->resolveBaseDirectory($platform);

        return $baseDir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Resolve the base directory where the database should be stored.
     */
    public function resolveBaseDirectory(?string $platform = null): string
    {
        $platform = $platform ?? $this->detector->detectOS();

        return match ($platform) {
            'android' => '/data/data/{package_id}/databases',
            'ios' => '{app_support}/databases',
            'windows' => $this->windowsDatabaseDir(),
            'macos' => $this->macosDatabaseDir(),
            'linux' => $this->linuxDatabaseDir(),
            default => $this->fallbackDatabaseDir(),
        };
    }

    /**
     * Resolve for a development environment (uses Laravel's database_path).
     */
    public function resolveForDev(): string
    {
        $filename = config('laranative.database.filename', 'database.sqlite');

        return database_path($filename);
    }

    /**
     * Windows: use LOCALAPPDATA/AppName/databases
     */
    protected function windowsDatabaseDir(): string
    {
        $appData = getenv('LOCALAPPDATA') ?: (getenv('APPDATA') ?: sys_get_temp_dir());
        $appName = $this->sanitizeAppName();

        return $appData . DIRECTORY_SEPARATOR . $appName . DIRECTORY_SEPARATOR . 'databases';
    }

    /**
     * macOS: use ~/Library/Application Support/AppName/databases
     */
    protected function macosDatabaseDir(): string
    {
        $home = getenv('HOME') ?: '/tmp';

        return $home . '/Library/Application Support/' . $this->sanitizeAppName() . '/databases';
    }

    /**
     * Linux: use XDG data dir or ~/.local/share/AppName/databases
     */
    protected function linuxDatabaseDir(): string
    {
        $dataDir = getenv('XDG_DATA_HOME') ?: ((getenv('HOME') ?: '/tmp') . '/.local/share');

        return $dataDir . '/' . $this->sanitizeAppName() . '/databases';
    }

    /**
     * Fallback: use Laravel's database directory.
     */
    protected function fallbackDatabaseDir(): string
    {
        if (function_exists('database_path')) {
            return database_path();
        }

        return sys_get_temp_dir();
    }

    /**
     * Sanitise the application name for use in filesystem paths.
     */
    protected function sanitizeAppName(): string
    {
        $name = config('laranative.name', config('app.name', 'LaraNativeApp'));

        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?: 'LaraNativeApp';
    }
}
