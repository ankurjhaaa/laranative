<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tymiqly\LaraNative\Build\BuildContext;
use Tymiqly\LaraNative\Build\BuildResult;

class BuildTest extends TestCase
{
    public function test_build_context_creation(): void
    {
        $context = new BuildContext(
            appName: 'Test App',
            appId: 'com.test.app',
            version: '1.0.0',
            platformName: 'android',
            mode: 'debug',
            laravelBasePath: '/project',
            buildBasePath: '/project/.laranative/build',
            outputBasePath: '/project/dist',
        );

        $this->assertSame('Test App', $context->appName());
        $this->assertSame('com.test.app', $context->appId());
        $this->assertSame('1.0.0', $context->version());
        $this->assertSame('android', $context->platform());
        $this->assertSame('debug', $context->buildMode());
        $this->assertTrue($context->isDebug());
        $this->assertFalse($context->isRelease());
    }

    public function test_build_context_release_mode(): void
    {
        $context = new BuildContext(
            appName: 'Test',
            appId: 'com.test.app',
            version: '1.0.0',
            platformName: 'android',
            mode: 'release',
            laravelBasePath: '/project',
            buildBasePath: '/build',
            outputBasePath: '/dist',
        );

        $this->assertTrue($context->isRelease());
        $this->assertFalse($context->isDebug());
    }

    public function test_build_context_paths(): void
    {
        $context = new BuildContext(
            appName: 'Test',
            appId: 'com.test.app',
            version: '1.0.0',
            platformName: 'android',
            mode: 'debug',
            laravelBasePath: '/project',
            buildBasePath: '/project/.laranative/build',
            outputBasePath: '/project/dist',
        );

        $this->assertSame('/project', $context->laravelPath());
        $this->assertStringEndsWith('android', $context->buildPath());
        $this->assertStringEndsWith('android', $context->outputPath());
    }

    public function test_build_context_options(): void
    {
        $context = new BuildContext(
            appName: 'Test',
            appId: 'com.test.app',
            version: '1.0.0',
            platformName: 'android',
            mode: 'debug',
            laravelBasePath: '/project',
            buildBasePath: '/build',
            outputBasePath: '/dist',
            options: ['arch' => 'arm64'],
        );

        $this->assertSame('arm64', $context->options()['arch']);

        $context->setOption('custom', 'value');
        $this->assertSame('value', $context->options()['custom']);
    }

    public function test_build_context_signing(): void
    {
        $context = new BuildContext(
            appName: 'Test',
            appId: 'com.test.app',
            version: '1.0.0',
            platformName: 'android',
            mode: 'release',
            laravelBasePath: '/project',
            buildBasePath: '/build',
            outputBasePath: '/dist',
            signing: ['keystore' => '/path/to/key'],
        );

        $this->assertTrue($context->shouldSign());
        $this->assertNotNull($context->signingConfig());
    }

    public function test_build_context_no_signing_in_debug(): void
    {
        $context = new BuildContext(
            appName: 'Test',
            appId: 'com.test.app',
            version: '1.0.0',
            platformName: 'android',
            mode: 'debug',
            laravelBasePath: '/project',
            buildBasePath: '/build',
            outputBasePath: '/dist',
            signing: ['keystore' => '/path/to/key'],
        );

        $this->assertFalse($context->shouldSign());
    }

    public function test_build_result_success(): void
    {
        $result = BuildResult::success('/dist/app.apk', 'Built', 12.5, ['Warning 1']);

        $this->assertTrue($result->succeeded());
        $this->assertSame('/dist/app.apk', $result->artifactPath());
        $this->assertSame('Built', $result->message());
        $this->assertSame(12.5, $result->duration());
        $this->assertCount(1, $result->warnings());
        $this->assertNull($result->error());
    }

    public function test_build_result_failure(): void
    {
        $result = BuildResult::failure('Gradle failed', 5.0);

        $this->assertFalse($result->succeeded());
        $this->assertNull($result->artifactPath());
        $this->assertSame('Gradle failed', $result->error());
        $this->assertSame(5.0, $result->duration());
    }
}
