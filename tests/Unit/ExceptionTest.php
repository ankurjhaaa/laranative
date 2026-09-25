<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tymiqly\LaraNative\Exceptions\LaraNativeException;
use Tymiqly\LaraNative\Exceptions\EnvironmentException;
use Tymiqly\LaraNative\Exceptions\BuildException;
use Tymiqly\LaraNative\Exceptions\PlatformException;
use Tymiqly\LaraNative\Exceptions\ConfigurationException;
use Tymiqly\LaraNative\Exceptions\SecurityException;

class ExceptionTest extends TestCase
{
    public function test_base_exception_hint(): void
    {
        $e = new LaraNativeException('Test error');
        $e->setHint('Try this fix');

        $this->assertSame('Try this fix', $e->getHint());
    }

    public function test_base_exception_docs_url(): void
    {
        $e = new LaraNativeException('Test');
        $e->setDocsUrl('https://example.com/docs');

        $this->assertSame('https://example.com/docs', $e->getDocsUrl());
    }

    public function test_base_exception_cli_output(): void
    {
        $e = new LaraNativeException('Something failed');
        $e->setHint('Install the SDK');
        $e->setDocsUrl('https://docs.example.com');

        $output = $e->toCliOutput();

        $this->assertStringContainsString('Something failed', $output);
        $this->assertStringContainsString('Install the SDK', $output);
        $this->assertStringContainsString('https://docs.example.com', $output);
    }

    public function test_environment_exception_tool_not_found(): void
    {
        $e = EnvironmentException::toolNotFound('gradle', 'brew install gradle');

        $this->assertStringContainsString('gradle', $e->getMessage());
        $this->assertStringContainsString('brew install gradle', $e->getHint());
    }

    public function test_environment_exception_unsupported_php(): void
    {
        $e = EnvironmentException::unsupportedPhpVersion('7.4', '8.1');

        $this->assertStringContainsString('7.4', $e->getMessage());
        $this->assertStringContainsString('8.1', $e->getHint());
    }

    public function test_build_exception_step_failed(): void
    {
        $e = BuildException::stepFailed('Validate', 'Missing config');

        $this->assertStringContainsString('Validate', $e->getMessage());
        $this->assertStringContainsString('Missing config', $e->getMessage());
    }

    public function test_platform_exception_unsupported(): void
    {
        $e = PlatformException::unsupportedPlatform('beos');

        $this->assertStringContainsString('beos', $e->getMessage());
    }

    public function test_platform_exception_requires_host(): void
    {
        $e = PlatformException::requiresHost('ios', 'macOS + Xcode');

        $this->assertStringContainsString('ios', $e->getMessage());
        $this->assertStringContainsString('macOS', $e->getMessage());
    }

    public function test_configuration_exception_missing(): void
    {
        $e = ConfigurationException::missing('laranative.id');

        $this->assertStringContainsString('laranative.id', $e->getMessage());
    }

    public function test_security_exception_debug_mode(): void
    {
        $e = SecurityException::debugModeEnabled();

        $this->assertStringContainsString('APP_DEBUG', $e->getMessage());
    }
}
