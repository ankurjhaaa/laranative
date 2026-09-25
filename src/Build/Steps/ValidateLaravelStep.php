<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * Validates that the current directory is a valid Laravel project.
 */
class ValidateLaravelStep extends BuildStep
{
    public function name(): string
    {
        return 'Validate Laravel project';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        $laravelPath = $context->laravelPath();

        // Check for artisan
        if (! file_exists($laravelPath . DIRECTORY_SEPARATOR . 'artisan')) {
            $output('  ✗ artisan file not found. Is this a Laravel project?');

            return false;
        }

        // Check for composer.json
        $composerFile = $laravelPath . DIRECTORY_SEPARATOR . 'composer.json';
        if (! file_exists($composerFile)) {
            $output('  ✗ composer.json not found');

            return false;
        }

        // Verify it's a Laravel project
        $composerJson = json_decode(file_get_contents($composerFile) ?: '{}', true);
        $require = $composerJson['require'] ?? [];

        if (! isset($require['laravel/framework'])) {
            $output('  ✗ laravel/framework not found in composer.json require');

            return false;
        }

        // Check for vendor
        if (! is_dir($laravelPath . DIRECTORY_SEPARATOR . 'vendor')) {
            $output('  ✗ vendor/ directory not found. Run: composer install');

            return false;
        }

        $output('  ✓ Laravel project validated');

        return true;
    }
}
