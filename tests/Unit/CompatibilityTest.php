<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tymiqly\LaraNative\Environment\Compatibility;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;

class CompatibilityTest extends TestCase
{
    public function test_minimum_php_version_constant(): void
    {
        $this->assertSame('8.1.0', Compatibility::MIN_PHP_VERSION);
    }

    public function test_supported_laravel_versions(): void
    {
        $this->assertContains(10, Compatibility::SUPPORTED_LARAVEL_VERSIONS);
        $this->assertContains(11, Compatibility::SUPPORTED_LARAVEL_VERSIONS);
        $this->assertContains(12, Compatibility::SUPPORTED_LARAVEL_VERSIONS);
        $this->assertContains(13, Compatibility::SUPPORTED_LARAVEL_VERSIONS);
    }

    public function test_required_extensions_include_sqlite(): void
    {
        $this->assertContains('pdo_sqlite', Compatibility::REQUIRED_EXTENSIONS);
        $this->assertContains('sqlite3', Compatibility::REQUIRED_EXTENSIONS);
    }

    public function test_check_returns_results_array(): void
    {
        $compatibility = new Compatibility();
        $detector = new EnvironmentDetector();

        $results = $compatibility->check($detector);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        foreach ($results as $result) {
            $this->assertArrayHasKey('check', $result);
            $this->assertArrayHasKey('passed', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('severity', $result);
        }
    }

    public function test_php_version_check_passes_on_current_runtime(): void
    {
        $compatibility = new Compatibility();
        $detector = new EnvironmentDetector();

        $results = $compatibility->check($detector);

        // Find PHP version check
        $phpCheck = null;
        foreach ($results as $result) {
            if ($result['check'] === 'PHP Version') {
                $phpCheck = $result;
                break;
            }
        }

        $this->assertNotNull($phpCheck, 'PHP Version check should exist');
        $this->assertTrue($phpCheck['passed'], 'PHP version should pass on PHP 8.1+');
    }

    public function test_all_critical_passed_returns_true_when_no_errors(): void
    {
        $compatibility = new Compatibility();

        $results = [
            ['check' => 'Test', 'passed' => true, 'message' => 'OK', 'severity' => 'error'],
            ['check' => 'Test2', 'passed' => false, 'message' => 'Warn', 'severity' => 'warning'],
        ];

        $this->assertTrue($compatibility->allCriticalPassed($results));
    }

    public function test_all_critical_passed_returns_false_when_error_fails(): void
    {
        $compatibility = new Compatibility();

        $results = [
            ['check' => 'Test', 'passed' => false, 'message' => 'Fail', 'severity' => 'error'],
        ];

        $this->assertFalse($compatibility->allCriticalPassed($results));
    }
}
