<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Platforms\PlatformManager;
use Tymiqly\LaraNative\Platforms\Android\AndroidBuilder;
use Tymiqly\LaraNative\Platforms\Android\AndroidProjectGenerator;
use Tymiqly\LaraNative\Platforms\Windows\WindowsBuilder;
use Tymiqly\LaraNative\Platforms\Linux\LinuxBuilder;
use Tymiqly\LaraNative\Platforms\MacOS\MacOSBuilder;
use Tymiqly\LaraNative\Platforms\IOS\IOSBuilder;

class PlatformManagerTest extends TestCase
{
    private PlatformManager $manager;
    private EnvironmentDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = new EnvironmentDetector();
        $this->manager = new PlatformManager($this->detector);

        $this->manager->register(new AndroidBuilder($this->detector, new AndroidProjectGenerator()));
        $this->manager->register(new WindowsBuilder($this->detector));
        $this->manager->register(new LinuxBuilder($this->detector));
        $this->manager->register(new MacOSBuilder($this->detector));
        $this->manager->register(new IOSBuilder($this->detector));
    }

    public function test_all_platforms_registered(): void
    {
        $platforms = $this->manager->supportedPlatforms();

        $this->assertContains('android', $platforms);
        $this->assertContains('windows', $platforms);
        $this->assertContains('linux', $platforms);
        $this->assertContains('macos', $platforms);
        $this->assertContains('ios', $platforms);
    }

    public function test_has_platform(): void
    {
        $this->assertTrue($this->manager->has('android'));
        $this->assertTrue($this->manager->has('Android'));
        $this->assertFalse($this->manager->has('nonexistent'));
    }

    public function test_get_builder(): void
    {
        $builder = $this->manager->builder('android');

        $this->assertInstanceOf(AndroidBuilder::class, $builder);
        $this->assertSame('android', $builder->platform());
        $this->assertSame('Android', $builder->displayName());
    }

    public function test_unsupported_platform_throws(): void
    {
        $this->expectException(\Tymiqly\LaraNative\Exceptions\PlatformException::class);

        $this->manager->builder('nonexistent');
    }

    public function test_availability_returns_all_platforms(): void
    {
        $availability = $this->manager->availability();

        $this->assertCount(5, $availability);

        foreach ($availability as $name => $info) {
            $this->assertArrayHasKey('available', $info);
            $this->assertArrayHasKey('reason', $info);
            $this->assertArrayHasKey('platform', $info);
        }
    }

    public function test_android_builder_properties(): void
    {
        $builder = $this->manager->builder('android');

        $this->assertContains('arm64-v8a', $builder->supportedArchitectures());
        $this->assertContains('apk', $builder->artifactExtensions());
        $this->assertContains('aab', $builder->artifactExtensions());
    }

    public function test_ios_requires_macos(): void
    {
        $builder = $this->manager->builder('ios');
        $check = $builder->checkAvailability();

        if (! $this->detector->isMacOS()) {
            $this->assertFalse($check['available']);
            $this->assertNotNull($check['reason']);
        }
    }

    public function test_windows_builder_check(): void
    {
        $builder = $this->manager->builder('windows');
        $check = $builder->checkAvailability();

        $this->assertSame($this->detector->isWindows(), $check['available']);
    }

    public function test_available_builders(): void
    {
        $available = $this->manager->availableBuilders();

        $this->assertIsArray($available);
        // At minimum, the current OS platform should be available
    }
}
