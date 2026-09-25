<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Environment;

/**
 * Compatibility checker for Laravel and PHP version validation.
 */
class Compatibility
{
    /**
     * Minimum supported PHP version.
     */
    public const MIN_PHP_VERSION = '8.1.0';

    /**
     * Supported Laravel major versions.
     */
    public const SUPPORTED_LARAVEL_VERSIONS = [10, 11, 12, 13];

    /**
     * Required PHP extensions.
     */
    public const REQUIRED_EXTENSIONS = [
        'pdo_sqlite',
        'sqlite3',
        'json',
        'mbstring',
        'openssl',
        'tokenizer',
        'xml',
        'ctype',
        'fileinfo',
    ];

    /**
     * Minimum Android API level.
     */
    public const MIN_ANDROID_API_LEVEL = 24;

    /**
     * Minimum Android SDK (compileSdk).
     */
    public const MIN_ANDROID_COMPILE_SDK = 34;

    /**
     * Target Android SDK.
     */
    public const TARGET_ANDROID_SDK = 35;

    /**
     * Minimum Gradle version.
     */
    public const MIN_GRADLE_VERSION = '8.0';

    /**
     * Minimum JDK version.
     */
    public const MIN_JDK_VERSION = '17';

    /**
     * Run all compatibility checks.
     *
     * @param  EnvironmentDetector  $detector
     * @return array<int, array{check: string, passed: bool, message: string, severity: string}>
     */
    public function check(EnvironmentDetector $detector): array
    {
        $results = [];

        // PHP version
        $results[] = [
            'check' => 'PHP Version',
            'passed' => version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>='),
            'message' => version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>=')
                ? 'PHP ' . PHP_VERSION . ' meets requirements'
                : 'PHP ' . PHP_VERSION . ' is below minimum ' . self::MIN_PHP_VERSION,
            'severity' => 'error',
        ];

        // PHP extensions
        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $results[] = [
                'check' => "PHP Extension: {$ext}",
                'passed' => $loaded,
                'message' => $loaded ? "Extension {$ext} is loaded" : "Extension {$ext} is missing",
                'severity' => in_array($ext, ['pdo_sqlite', 'sqlite3']) ? 'error' : 'warning',
            ];
        }

        // Laravel version
        $laravelVersion = $detector->detectLaravelVersion();
        $laravelMajor = $detector->laravelMajorVersion();

        if ($laravelVersion !== null) {
            $supported = $laravelMajor !== null && in_array($laravelMajor, self::SUPPORTED_LARAVEL_VERSIONS, true);
            $results[] = [
                'check' => 'Laravel Version',
                'passed' => $supported,
                'message' => $supported
                    ? "Laravel {$laravelVersion} is supported"
                    : "Laravel {$laravelVersion} is not supported. Supported: " . implode(', ', self::SUPPORTED_LARAVEL_VERSIONS),
                'severity' => 'error',
            ];
        } else {
            $results[] = [
                'check' => 'Laravel Version',
                'passed' => false,
                'message' => 'Could not detect Laravel version. Is this a Laravel project?',
                'severity' => 'error',
            ];
        }

        // Composer
        $composer = $detector->detectTool('composer');
        $results[] = [
            'check' => 'Composer',
            'passed' => $composer['installed'],
            'message' => $composer['installed']
                ? "Composer {$composer['version']} found"
                : 'Composer is not installed. Install from https://getcomposer.org',
            'severity' => 'error',
        ];

        return $results;
    }

    /**
     * Check if all critical requirements pass.
     *
     * @param  array<int, array{check: string, passed: bool, message: string, severity: string}>  $results
     */
    public function allCriticalPassed(array $results): bool
    {
        foreach ($results as $result) {
            if ($result['severity'] === 'error' && ! $result['passed']) {
                return false;
            }
        }

        return true;
    }
}
