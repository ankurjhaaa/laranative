<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Storage\LaraNativeStorage;

class StorageTest extends TestCase
{
    public function test_platform_detection(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);

        $this->assertContains($storage->platform(), ['windows', 'macos', 'linux']);
    }

    public function test_set_platform(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);

        $storage->setPlatform('android');
        $this->assertSame('android', $storage->platform());
    }

    public function test_override_base_path(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);

        $storage->setBasePath('/custom/path');
        $this->assertSame('/custom/path', $storage->basePath());
    }

    public function test_resolve_subdirectories(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);
        $storage->setBasePath('/base');

        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'app', $storage->appDataPath());
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'cache', $storage->cachePath());
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'logs', $storage->logPath());
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'databases', $storage->databasePath());
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'user_files', $storage->userFilesPath());
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'temp', $storage->tempPath());
    }

    public function test_resolve_relative_path(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);
        $storage->setBasePath('/base');

        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'custom/dir', $storage->resolve('custom/dir'));
    }

    public function test_ensure_directories_with_override_path(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laranative_test_' . uniqid();
        $storage->setBasePath($tempDir);

        $storage->ensureDirectories();

        $this->assertDirectoryExists($tempDir . DIRECTORY_SEPARATOR . 'app');
        $this->assertDirectoryExists($tempDir . DIRECTORY_SEPARATOR . 'cache');
        $this->assertDirectoryExists($tempDir . DIRECTORY_SEPARATOR . 'logs');
        $this->assertDirectoryExists($tempDir . DIRECTORY_SEPARATOR . 'databases');

        // Cleanup
        $this->removeDirectory($tempDir);
    }

    public function test_android_platform_uses_template_paths(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);
        $storage->setPlatform('android');

        $base = $storage->basePath();
        $this->assertStringContainsString('{package_id}', $base);
    }

    public function test_available_space_returns_null_for_template_paths(): void
    {
        $detector = new EnvironmentDetector();
        $storage = new LaraNativeStorage($detector);
        $storage->setPlatform('android');

        $this->assertNull($storage->availableSpace());
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }

        rmdir($dir);
    }
}
