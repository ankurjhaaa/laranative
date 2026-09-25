<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Security\SecurityValidator;

/**
 * Runs security validation before building.
 */
class SecurityValidationStep extends BuildStep
{
    public function __construct(
        protected SecurityValidator $validator
    ) {}

    public function name(): string
    {
        return 'Security validation';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        $results = $this->validator->validate($context->buildMode());
        $hasErrors = false;

        foreach ($results as $result) {
            $icon = $result['passed'] ? '✓' : ($result['severity'] === 'error' ? '✗' : '⚠');
            $output("  {$icon} {$result['check']}: {$result['message']}");

            if (! $result['passed'] && $result['severity'] === 'error') {
                $hasErrors = true;
            }
        }

        // Scan .env file
        $envPath = $context->laravelPath() . DIRECTORY_SEPARATOR . '.env';
        $exposedSecrets = $this->validator->scanEnvFile($envPath);

        if (! empty($exposedSecrets)) {
            $output('  ✗ Secrets found in .env that should NOT be in client builds:');
            foreach ($exposedSecrets as $key) {
                $output("    - {$key}");
            }

            if ($context->isRelease()) {
                $hasErrors = true;
            }
        }

        if ($hasErrors && $context->isRelease()) {
            $output('  ✗ Security validation failed for release build.');

            return false;
        }

        return true;
    }

    /**
     * Skip security validation in debug mode if not strict.
     */
    public function shouldSkip(BuildContextInterface $context): bool
    {
        return false; // Always run, but only fail for release builds
    }
}
