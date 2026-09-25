<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Integration;

use Tymiqly\LaraNative\Tests\TestCase;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Environment\Compatibility;
use Tymiqly\LaraNative\Database\DatabasePathResolver;
use Tymiqly\LaraNative\Database\SQLiteManager;
use Tymiqly\LaraNative\Platforms\PlatformManager;
use Tymiqly\LaraNative\Storage\LaraNativeStorage;
use Tymiqly\LaraNative\Security\SecurityValidator;
use Tymiqly\LaraNative\Native\ConnectivityManager;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('laranative'));
        $this->assertNotNull(config('laranative.name'));
        $this->assertNotNull(config('laranative.id'));
        $this->assertNotNull(config('laranative.version'));
    }

    public function test_environment_detector_is_singleton(): void
    {
        $first = $this->app->make(EnvironmentDetector::class);
        $second = $this->app->make(EnvironmentDetector::class);

        $this->assertSame($first, $second);
    }

    public function test_compatibility_is_registered(): void
    {
        $this->assertInstanceOf(
            Compatibility::class,
            $this->app->make(Compatibility::class)
        );
    }

    public function test_database_path_resolver_is_registered(): void
    {
        $this->assertInstanceOf(
            DatabasePathResolver::class,
            $this->app->make(DatabasePathResolver::class)
        );
    }

    public function test_sqlite_manager_is_registered(): void
    {
        $this->assertInstanceOf(
            SQLiteManager::class,
            $this->app->make(SQLiteManager::class)
        );
    }

    public function test_platform_manager_is_singleton(): void
    {
        $first = $this->app->make(PlatformManager::class);
        $second = $this->app->make(PlatformManager::class);

        $this->assertSame($first, $second);
    }

    public function test_platform_manager_has_all_platforms(): void
    {
        $manager = $this->app->make(PlatformManager::class);

        $this->assertTrue($manager->has('android'));
        $this->assertTrue($manager->has('windows'));
        $this->assertTrue($manager->has('linux'));
        $this->assertTrue($manager->has('macos'));
        $this->assertTrue($manager->has('ios'));
    }

    public function test_storage_is_registered(): void
    {
        $this->assertInstanceOf(
            LaraNativeStorage::class,
            $this->app->make(LaraNativeStorage::class)
        );
    }

    public function test_security_validator_is_registered(): void
    {
        $this->assertInstanceOf(
            SecurityValidator::class,
            $this->app->make(SecurityValidator::class)
        );
    }

    public function test_connectivity_manager_is_registered(): void
    {
        $this->assertInstanceOf(
            ConnectivityManager::class,
            $this->app->make(ConnectivityManager::class)
        );
    }

    public function test_artisan_commands_are_registered(): void
    {
        $commands = [
            'laranative:install',
            'laranative:setup',
            'laranative:doctor',
            'laranative:status',
            'laranative:dev',
            'laranative:build',
            'laranative:clean',
            'laranative:logs',
        ];

        foreach ($commands as $command) {
            $this->assertTrue(
                array_key_exists($command, \Illuminate\Support\Facades\Artisan::all()),
                "Command '{$command}' should be registered"
            );
        }
    }

    public function test_config_has_platform_sections(): void
    {
        $this->assertNotNull(config('laranative.platforms.android'));
        $this->assertNotNull(config('laranative.platforms.ios'));
        $this->assertNotNull(config('laranative.platforms.windows'));
        $this->assertNotNull(config('laranative.platforms.macos'));
        $this->assertNotNull(config('laranative.platforms.linux'));
    }

    public function test_config_has_security_section(): void
    {
        $this->assertNotNull(config('laranative.security'));
        $this->assertNotNull(config('laranative.security.forbidden_env_keys'));
        $this->assertIsArray(config('laranative.security.forbidden_env_keys'));
    }

    public function test_config_has_services_section(): void
    {
        $services = config('laranative.services');

        $this->assertIsArray($services);
        $this->assertSame('offline', $services['auth']);
        $this->assertSame('online-required', $services['payment']);
    }
}
