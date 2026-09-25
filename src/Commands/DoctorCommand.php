<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Tymiqly\LaraNative\Environment\Compatibility;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Platforms\PlatformManager;
use Tymiqly\LaraNative\Security\SecurityValidator;

/**
 * Diagnoses the development environment and reports issues.
 */
class DoctorCommand extends BaseCommand
{
    protected $signature = 'laranative:doctor {--debug : Show detailed output}';

    protected $description = 'Check your environment for LaraNative compatibility';

    public function handle(
        EnvironmentDetector $detector,
        Compatibility $compatibility,
        PlatformManager $platformManager,
        SecurityValidator $securityValidator,
    ): int {
        $this->banner();
        $this->line('Running diagnostics...');

        $hasErrors = false;

        // System info
        $this->section('System');
        $env = $detector->detect();
        $this->keyValue('OS', $env['os'] . ' (' . $env['architecture'] . ')');
        $this->keyValue('PHP', $env['php_version']);
        $this->keyValue('Laravel', $env['laravel_version'] ?? 'Not detected');

        // Compatibility checks
        $this->section('Compatibility');
        $results = $compatibility->check($detector);

        foreach ($results as $result) {
            if ($result['passed']) {
                $this->check($result['message']);
            } elseif ($result['severity'] === 'error') {
                $this->failure($result['message']);
                $hasErrors = true;
            } else {
                $this->warning($result['message']);
            }
        }

        // Tools
        $this->section('Tools');
        $tools = ['composer', 'node', 'npm', 'git'];

        foreach ($tools as $tool) {
            $info = $env[$tool] ?? null;
            if ($info && $info['installed']) {
                $this->check("{$tool}: {$info['version']}");
            } else {
                $this->warning("{$tool}: not found");
            }
        }

        // Platform availability
        $this->section('Platform Availability');
        $availability = $platformManager->availability();

        foreach ($availability as $name => $info) {
            if ($info['available']) {
                $this->check("{$info['platform']}: <fg=green>available</>");
            } else {
                $this->warning("{$info['platform']}: {$info['reason']}");
            }
        }

        // Security
        $this->section('Security');
        $secResults = $securityValidator->validate('release');

        foreach ($secResults as $result) {
            if ($result['passed']) {
                $this->check($result['message']);
            } elseif ($result['severity'] === 'error') {
                $this->failure($result['message']);
            } else {
                $this->warning($result['message']);
            }
        }

        // Configuration
        $this->section('Configuration');
        $configPath = config_path('laranative.php');

        if (file_exists($configPath)) {
            $this->check('config/laranative.php found');
            $this->keyValue('App Name', config('laranative.name', 'not set'));
            $this->keyValue('App ID', config('laranative.id', 'not set'));
            $this->keyValue('Version', config('laranative.version', 'not set'));
        } else {
            $this->failure('config/laranative.php not found. Run: php artisan laranative:install');
            $hasErrors = true;
        }

        // Summary
        $this->newLine();
        if ($hasErrors) {
            $this->line('<fg=red;options=bold>Some checks failed. Please fix the issues above.</>');

            return self::FAILURE;
        }

        $this->line('<fg=green;options=bold>All checks passed!</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
