<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Storage;

use Tymiqly\LaraNative\Contracts\StorageDriverInterface;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;

/**
 * Platform-aware local storage manager.
 *
 * Resolves storage directories dynamically according to the target platform
 * and ensures all required directories exist.
 */
class LaraNativeStorage implements StorageDriverInterface
{
    protected string $currentPlatform;

    protected ?string $overrideBasePath = null;

    public function __construct(
        protected EnvironmentDetector $detector
    ) {
        $this->currentPlatform = $detector->detectOS();
    }

    /**
     * Set the platform (used during builds for a target platform).
     */
    public function setPlatform(string $platform): static
    {
        $this->currentPlatform = $platform;

        return $this;
    }

    /**
     * Override the base path (useful for testing).
     */
    public function setBasePath(string $path): static
    {
        $this->overrideBasePath = $path;

        return $this;
    }

    public function platform(): string
    {
        return $this->currentPlatform;
    }

    public function basePath(): string
    {
        if ($this->overrideBasePath) {
            return $this->overrideBasePath;
        }

        return match ($this->currentPlatform) {
            'android' => '/data/data/{package_id}/files',
            'ios' => '{app_support}',
            'windows' => $this->windowsBasePath(),
            'macos' => $this->macosBasePath(),
            'linux' => $this->linuxBasePath(),
            default => $this->fallbackBasePath(),
        };
    }

    public function appDataPath(): string
    {
        return $this->resolve('app');
    }

    public function cachePath(): string
    {
        return $this->resolve('cache');
    }

    public function logPath(): string
    {
        return $this->resolve('logs');
    }

    public function databasePath(): string
    {
        return $this->resolve('databases');
    }

    public function userFilesPath(): string
    {
        return $this->resolve('user_files');
    }

    public function tempPath(): string
    {
        return $this->resolve('temp');
    }

    public function resolve(string $relativePath): string
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . $relativePath;
    }

    public function ensureDirectories(): void
    {
        $dirs = [
            $this->appDataPath(),
            $this->cachePath(),
            $this->logPath(),
            $this->databasePath(),
            $this->userFilesPath(),
            $this->tempPath(),
        ];

        foreach ($dirs as $dir) {
            // Skip template paths (platform placeholders like {package_id})
            if (str_contains($dir, '{')) {
                continue;
            }

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function availableSpace(): ?int
    {
        $path = $this->basePath();

        if (str_contains($path, '{')) {
            return null;
        }

        if (! is_dir($path)) {
            $path = dirname($path);
        }

        if (! is_dir($path)) {
            return null;
        }

        $space = @disk_free_space($path);

        return $space !== false ? (int) $space : null;
    }

    protected function windowsBasePath(): string
    {
        $appData = getenv('LOCALAPPDATA') ?: (getenv('APPDATA') ?: sys_get_temp_dir());

        return $appData . DIRECTORY_SEPARATOR . $this->sanitizeAppName();
    }

    protected function macosBasePath(): string
    {
        $home = getenv('HOME') ?: '/tmp';

        return $home . '/Library/Application Support/' . $this->sanitizeAppName();
    }

    protected function linuxBasePath(): string
    {
        $dataDir = getenv('XDG_DATA_HOME') ?: ((getenv('HOME') ?: '/tmp') . '/.local/share');

        return $dataDir . '/' . $this->sanitizeAppName();
    }

    protected function fallbackBasePath(): string
    {
        if (function_exists('storage_path')) {
            return storage_path('laranative');
        }

        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laranative';
    }

    protected function sanitizeAppName(): string
    {
        $name = config('laranative.name', config('app.name', 'LaraNativeApp'));

        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?: 'LaraNativeApp';
    }
}
