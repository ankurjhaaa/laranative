<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Exceptions\ConfigurationException;

/**
 * Validates the LaraNative configuration.
 */
class ValidateConfigStep extends BuildStep
{
    public function name(): string
    {
        return 'Validate LaraNative configuration';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        $errors = [];

        // Check app name
        $appName = $context->appName();
        if (empty($appName) || $appName === 'LaraNative App') {
            $output('  ⚠ App name is using default. Set laranative.name in config.');
        }

        // Check app ID
        $appId = $context->appId();
        if (empty($appId) || $appId === 'com.example.app') {
            $errors[] = 'Application ID is using default (com.example.app). Set laranative.id in config.';
        }

        // Validate app ID format
        if (! preg_match('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9]*)+$/', $appId)) {
            $errors[] = "Application ID '{$appId}' is invalid. Use reverse-domain notation (e.g. com.company.app).";
        }

        // Check version
        $version = $context->version();
        if (! preg_match('/^\d+\.\d+\.\d+/', $version)) {
            $errors[] = "Version '{$version}' is not valid semantic versioning.";
        }

        // Check config file exists
        $configPath = $context->laravelPath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'laranative.php';
        if (! file_exists($configPath)) {
            $output('  ⚠ config/laranative.php not found. Run: php artisan laranative:install');
        }

        if (! empty($errors)) {
            foreach ($errors as $error) {
                $output("  ✗ {$error}");
            }

            return false;
        }

        $output('  ✓ Configuration validated');

        return true;
    }
}
