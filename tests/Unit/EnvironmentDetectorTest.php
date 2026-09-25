<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;

class EnvironmentDetectorTest extends TestCase
{
    private EnvironmentDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new EnvironmentDetector();
    }

    public function test_detects_current_os(): void
    {
        $os = $this->detector->detectOS();

        $this->assertContains($os, ['windows', 'macos', 'linux']);
    }

    public function test_detects_os_family(): void
    {
        $family = $this->detector->osFamily();

        $this->assertContains($family, ['Windows', 'Darwin', 'Linux']);
    }

    public function test_os_detection_methods_are_consistent(): void
    {
        $os = $this->detector->detectOS();

        match ($os) {
            'windows' => $this->assertTrue($this->detector->isWindows()),
            'macos' => $this->assertTrue($this->detector->isMacOS()),
            'linux' => $this->assertTrue($this->detector->isLinux()),
        };
    }

    public function test_detects_php_version(): void
    {
        $env = $this->detector->detect();

        $this->assertSame(PHP_VERSION, $env['php_version']);
        $this->assertSame(PHP_MAJOR_VERSION, $env['php_major']);
    }

    public function test_php_version_is_supported(): void
    {
        // Test is running on PHP 8.1+, so it should pass
        $this->assertTrue($this->detector->isPhpSupported());
    }

    public function test_detects_php_extensions(): void
    {
        $extensions = $this->detector->detectExtensions();

        $this->assertIsArray($extensions);
        $this->assertArrayHasKey('json', $extensions);
        $this->assertTrue($extensions['json'], 'JSON extension should be loaded');
    }

    public function test_detects_missing_extensions(): void
    {
        $missing = $this->detector->missingExtensions();

        $this->assertIsArray($missing);
        // json should not be in missing
        $this->assertNotContains('json', $missing);
    }

    public function test_detect_results_are_cached(): void
    {
        $first = $this->detector->detect();
        $second = $this->detector->detect();

        $this->assertSame($first, $second);
    }

    public function test_cache_can_be_cleared(): void
    {
        $this->detector->detect();
        $this->detector->clearCache();

        // After clearing, calling detect again should work
        $result = $this->detector->detect();
        $this->assertNotEmpty($result);
    }

    public function test_detects_composer(): void
    {
        $composer = $this->detector->detectTool('composer');

        $this->assertArrayHasKey('installed', $composer);
        $this->assertArrayHasKey('version', $composer);
        $this->assertArrayHasKey('path', $composer);
    }

    public function test_supported_laravel_versions_string(): void
    {
        $versions = $this->detector->supportedLaravelVersions();

        $this->assertStringContainsString('10', $versions);
        $this->assertStringContainsString('11', $versions);
        $this->assertStringContainsString('12', $versions);
    }

    public function test_platform_availability_returns_all_platforms(): void
    {
        $availability = $this->detector->platformAvailability();

        $this->assertArrayHasKey('android', $availability);
        $this->assertArrayHasKey('windows', $availability);
        $this->assertArrayHasKey('linux', $availability);
        $this->assertArrayHasKey('macos', $availability);
        $this->assertArrayHasKey('ios', $availability);

        foreach ($availability as $platform => $info) {
            $this->assertArrayHasKey('available', $info);
            $this->assertArrayHasKey('reason', $info);
            $this->assertIsBool($info['available']);
        }
    }

    public function test_detects_android_sdk(): void
    {
        $sdk = $this->detector->detectAndroidSdk();

        $this->assertArrayHasKey('installed', $sdk);
        $this->assertArrayHasKey('path', $sdk);
        $this->assertArrayHasKey('api_level', $sdk);
        $this->assertArrayHasKey('build_tools', $sdk);
    }

    public function test_architecture_is_detected(): void
    {
        $env = $this->detector->detect();

        $this->assertNotEmpty($env['architecture']);
    }
}
