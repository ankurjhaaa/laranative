<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Security;

use Illuminate\Support\Facades\Config;

/**
 * Validates the security posture of a LaraNative build.
 *
 * Checks for exposed secrets, debug mode, insecure configurations,
 * and other production risks.
 */
class SecurityValidator
{
    /**
     * Run all security checks.
     *
     * @param  string  $buildMode  'debug' or 'release'
     * @return array<int, array{check: string, passed: bool, message: string, severity: string}>
     */
    public function validate(string $buildMode = 'release'): array
    {
        $results = [];

        if ($buildMode === 'release') {
            $results[] = $this->checkDebugMode();
            $results[] = $this->checkAppEnv();
        }

        $results = array_merge($results, $this->checkForbiddenEnvKeys());
        $results[] = $this->checkSyncUrl();
        $results[] = $this->checkAppKey();

        return array_filter($results);
    }

    /**
     * Check if APP_DEBUG is disabled for release builds.
     *
     * @return array{check: string, passed: bool, message: string, severity: string}
     */
    protected function checkDebugMode(): array
    {
        $debug = config('app.debug', false);

        return [
            'check' => 'APP_DEBUG',
            'passed' => ! $debug,
            'message' => $debug
                ? 'APP_DEBUG=true is unsafe for production/release builds. Set APP_DEBUG=false.'
                : 'APP_DEBUG is disabled',
            'severity' => 'error',
        ];
    }

    /**
     * Check if APP_ENV is set to production.
     *
     * @return array{check: string, passed: bool, message: string, severity: string}
     */
    protected function checkAppEnv(): array
    {
        $env = config('app.env', 'production');

        return [
            'check' => 'APP_ENV',
            'passed' => $env === 'production',
            'message' => $env !== 'production'
                ? "APP_ENV is '{$env}'. Consider setting to 'production' for release builds."
                : 'APP_ENV is set to production',
            'severity' => 'warning',
        ];
    }

    /**
     * Check for forbidden environment keys that should never be in client builds.
     *
     * @return array<int, array{check: string, passed: bool, message: string, severity: string}>
     */
    protected function checkForbiddenEnvKeys(): array
    {
        $forbidden = config('laranative.security.forbidden_env_keys', []);
        $results = [];

        foreach ($forbidden as $key) {
            $value = env($key);
            $hasValue = $value !== null && $value !== '';

            if ($hasValue) {
                $results[] = [
                    'check' => "Env: {$key}",
                    'passed' => false,
                    'message' => "Forbidden environment variable '{$key}' is set. This value should NOT be included in client builds.",
                    'severity' => 'error',
                ];
            }
        }

        if (empty($results)) {
            $results[] = [
                'check' => 'Forbidden Environment Variables',
                'passed' => true,
                'message' => 'No forbidden environment variables detected',
                'severity' => 'info',
            ];
        }

        return $results;
    }

    /**
     * Check that the sync URL uses HTTPS.
     *
     * @return array{check: string, passed: bool, message: string, severity: string}
     */
    protected function checkSyncUrl(): array
    {
        $syncUrl = config('laranative.sync.url');

        if (! $syncUrl) {
            return [
                'check' => 'Sync URL',
                'passed' => true,
                'message' => 'Sync is not configured (no URL set)',
                'severity' => 'info',
            ];
        }

        $isHttps = str_starts_with($syncUrl, 'https://');
        $enforceHttps = config('laranative.security.enforce_https_sync', true);

        return [
            'check' => 'Sync URL Security',
            'passed' => $isHttps || ! $enforceHttps,
            'message' => ! $isHttps
                ? "Sync URL '{$syncUrl}' does not use HTTPS. This is a security risk."
                : 'Sync URL uses HTTPS',
            'severity' => $enforceHttps ? 'error' : 'warning',
        ];
    }

    /**
     * Check APP_KEY presence (it IS needed for encryption/sessions, but flag awareness).
     *
     * @return array{check: string, passed: bool, message: string, severity: string}
     */
    protected function checkAppKey(): array
    {
        $key = config('app.key');

        return [
            'check' => 'APP_KEY',
            'passed' => ! empty($key),
            'message' => empty($key)
                ? 'APP_KEY is not set. Run `php artisan key:generate` first.'
                : 'APP_KEY is set. Note: This key will be embedded in the build for session/encryption functionality.',
            'severity' => empty($key) ? 'error' : 'info',
        ];
    }

    /**
     * Scan the .env file for any secrets that should not be bundled.
     *
     * @param  string  $envPath  Path to the .env file.
     * @return array<string>  List of found secret key names.
     */
    public function scanEnvFile(string $envPath): array
    {
        if (! file_exists($envPath)) {
            return [];
        }

        $forbidden = config('laranative.security.forbidden_env_keys', []);
        $found = [];

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);

                if (in_array($key, $forbidden, true) && $value !== '' && $value !== 'null') {
                    $found[] = $key;
                }
            }
        }

        return $found;
    }
}
