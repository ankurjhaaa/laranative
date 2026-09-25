<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * Generates a clean, safe .env file for the build.
 * Excludes sensitive credentials like DB_PASSWORD, AWS keys, etc.
 */
class GenerateEnvStep extends BuildStep
{
    public function name(): string
    {
        return 'Generate environment variables';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        $sourceEnvPath = $context->laravelPath() . DIRECTORY_SEPARATOR . '.env';
        $targetEnvPath = $context->buildPath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . '.env';

        if (! file_exists($sourceEnvPath)) {
            $output('  ⚠ Source .env file not found. Generating a basic one.');
            $this->generateBasicEnv($targetEnvPath);
            return true;
        }

        $allowedKeys = $context->config('security.allowed_env_keys', [
            'APP_NAME',
            'APP_ENV',
            'APP_URL',
            'APP_LOCALE',
            'APP_TIMEZONE',
            'LOG_CHANNEL',
            'LOG_LEVEL',
            'SESSION_DRIVER',
            'SESSION_LIFETIME',
            'CACHE_STORE',
        ]);

        // Always allow APP_KEY
        if (! in_array('APP_KEY', $allowedKeys)) {
            $allowedKeys[] = 'APP_KEY';
        }
        
        // Force offline DB for client
        if (! in_array('DB_CONNECTION', $allowedKeys)) {
            $allowedKeys[] = 'DB_CONNECTION';
        }

        $lines = file($sourceEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Keep comments
            if (str_starts_with($line, '#')) {
                $newLines[] = $line;
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                
                if (in_array($key, $allowedKeys)) {
                    // Override APP_ENV for release builds
                    if ($key === 'APP_ENV' && $context->isRelease()) {
                        $newLines[] = 'APP_ENV=production';
                        continue;
                    }
                    
                    if ($key === 'DB_CONNECTION') {
                        $newLines[] = 'DB_CONNECTION=sqlite';
                        continue;
                    }
                    
                    $newLines[] = $line;
                }
            }
        }

        file_put_contents($targetEnvPath, implode(PHP_EOL, $newLines) . PHP_EOL);
        
        $output('  ✓ Generated clean .env file for client');

        return true;
    }

    protected function generateBasicEnv(string $path): void
    {
        $content = <<<ENV
APP_NAME=LaraNative
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite
DB_FOREIGN_KEYS=true

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
ENV;
        file_put_contents($path, $content);
    }
}
