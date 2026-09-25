<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Environment;

use Illuminate\Support\Facades\App;
use Tymiqly\LaraNative\Exceptions\EnvironmentException;

/**
 * Detects the host environment: OS, architecture, PHP version, Laravel version,
 * and available platform SDKs/tools.
 */
class EnvironmentDetector
{
    /**
     * Cached detection results.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $cache = null;

    /**
     * Run full environment detection and return results.
     *
     * @return array<string, mixed>
     */
    public function detect(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = [
            'os' => $this->detectOS(),
            'os_family' => PHP_OS_FAMILY,
            'architecture' => php_uname('m'),
            'php_version' => PHP_VERSION,
            'php_major' => PHP_MAJOR_VERSION,
            'php_minor' => PHP_MINOR_VERSION,
            'php_extensions' => $this->detectExtensions(),
            'laravel_version' => $this->detectLaravelVersion(),
            'composer' => $this->detectTool('composer'),
            'node' => $this->detectTool('node'),
            'npm' => $this->detectTool('npm'),
            'npx' => $this->detectTool('npx'),
            'git' => $this->detectTool('git'),
            'android_sdk' => $this->detectAndroidSdk(),
            'java' => $this->detectJava(),
            'gradle' => $this->detectTool('gradle'),
            'xcode' => $this->detectXcode(),
            'hostname' => gethostname() ?: 'unknown',
        ];

        return $this->cache;
    }

    /**
     * Detect the operating system.
     */
    public function detectOS(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'windows',
            'Darwin' => 'macos',
            'Linux' => 'linux',
            default => strtolower(PHP_OS_FAMILY),
        };
    }

    /**
     * Get the host OS family.
     */
    public function osFamily(): string
    {
        return PHP_OS_FAMILY;
    }

    /**
     * Check if host is Windows.
     */
    public function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Check if host is macOS.
     */
    public function isMacOS(): bool
    {
        return PHP_OS_FAMILY === 'Darwin';
    }

    /**
     * Check if host is Linux.
     */
    public function isLinux(): bool
    {
        return PHP_OS_FAMILY === 'Linux';
    }

    /**
     * Detect the Laravel version from the application.
     */
    public function detectLaravelVersion(): ?string
    {
        if (class_exists(\Illuminate\Foundation\Application::class)) {
            return \Illuminate\Foundation\Application::VERSION;
        }

        return null;
    }

    /**
     * Get the major Laravel version number.
     */
    public function laravelMajorVersion(): ?int
    {
        $version = $this->detectLaravelVersion();

        if ($version === null) {
            return null;
        }

        $parts = explode('.', $version);

        return (int) $parts[0];
    }

    /**
     * Check if the current Laravel version is supported.
     */
    public function isLaravelSupported(): bool
    {
        $major = $this->laravelMajorVersion();

        return $major !== null && $major >= 10 && $major <= 13;
    }

    /**
     * Get supported Laravel version range as a string.
     */
    public function supportedLaravelVersions(): string
    {
        return '10.x, 11.x, 12.x, 13.x';
    }

    /**
     * Check if the PHP version meets minimum requirements.
     */
    public function isPhpSupported(): bool
    {
        return PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION >= 1;
    }

    /**
     * Detect available PHP extensions.
     *
     * @return array<string, bool>
     */
    public function detectExtensions(): array
    {
        $required = ['pdo_sqlite', 'sqlite3', 'json', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'fileinfo'];

        $result = [];
        foreach ($required as $ext) {
            $result[$ext] = extension_loaded($ext);
        }

        return $result;
    }

    /**
     * Get missing required PHP extensions.
     *
     * @return array<string>
     */
    public function missingExtensions(): array
    {
        $missing = [];
        foreach ($this->detectExtensions() as $ext => $loaded) {
            if (! $loaded) {
                $missing[] = $ext;
            }
        }

        return $missing;
    }

    /**
     * Detect a CLI tool and return its version.
     *
     * @param  string  $tool
     * @return array{installed: bool, version: string|null, path: string|null}
     */
    public function detectTool(string $tool): array
    {
        $command = $this->isWindows() ? "where {$tool} 2>NUL" : "which {$tool} 2>/dev/null";
        $path = $this->executeCommand($command);

        if (! $path) {
            return ['installed' => false, 'version' => null, 'path' => null];
        }

        // Take first line if multiple paths returned
        $path = explode("\n", trim($path))[0];

        $versionCommand = match ($tool) {
            'composer' => 'composer --version 2>&1',
            'node' => 'node --version 2>&1',
            'npm' => 'npm --version 2>&1',
            'npx' => 'npx --version 2>&1',
            'git' => 'git --version 2>&1',
            'gradle' => 'gradle --version 2>&1',
            'java' => 'java -version 2>&1',
            default => null,
        };

        $version = null;
        if ($versionCommand) {
            $output = $this->executeCommand($versionCommand);
            if ($output) {
                $version = $this->parseVersion($tool, $output);
            }
        }

        return ['installed' => true, 'version' => $version, 'path' => trim($path)];
    }

    /**
     * Detect the Android SDK.
     *
     * @return array{installed: bool, path: string|null, api_level: int|null, build_tools: string|null}
     */
    public function detectAndroidSdk(): array
    {
        $sdkPath = $this->findAndroidSdkPath();

        if (! $sdkPath) {
            return ['installed' => false, 'path' => null, 'api_level' => null, 'build_tools' => null];
        }

        $apiLevel = $this->detectAndroidApiLevel($sdkPath);
        $buildTools = $this->detectAndroidBuildTools($sdkPath);

        return [
            'installed' => true,
            'path' => $sdkPath,
            'api_level' => $apiLevel,
            'build_tools' => $buildTools,
        ];
    }

    /**
     * Find the Android SDK path from environment variables or common locations.
     */
    protected function findAndroidSdkPath(): ?string
    {
        // Check environment variables
        $envPaths = ['ANDROID_HOME', 'ANDROID_SDK_ROOT'];
        foreach ($envPaths as $env) {
            $path = getenv($env);
            if ($path && is_dir($path)) {
                return $path;
            }
        }

        // Check common locations
        $commonPaths = [];

        if ($this->isWindows()) {
            $localAppData = getenv('LOCALAPPDATA') ?: '';
            $commonPaths = [
                $localAppData . '\\Android\\Sdk',
                getenv('USERPROFILE') . '\\AppData\\Local\\Android\\Sdk',
                'C:\\Android\\Sdk',
            ];
        } elseif ($this->isMacOS()) {
            $home = getenv('HOME') ?: '';
            $commonPaths = [
                $home . '/Library/Android/sdk',
                '/usr/local/share/android-sdk',
            ];
        } else {
            $home = getenv('HOME') ?: '';
            $commonPaths = [
                $home . '/Android/Sdk',
                '/usr/local/android-sdk',
                '/opt/android-sdk',
            ];
        }

        foreach ($commonPaths as $path) {
            if ($path && is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Detect the highest installed Android API level.
     */
    protected function detectAndroidApiLevel(string $sdkPath): ?int
    {
        $platformsDir = $sdkPath . DIRECTORY_SEPARATOR . 'platforms';

        if (! is_dir($platformsDir)) {
            return null;
        }

        $levels = [];
        foreach (scandir($platformsDir) ?: [] as $dir) {
            if (preg_match('/^android-(\d+)$/', $dir, $matches)) {
                $levels[] = (int) $matches[1];
            }
        }

        return $levels ? max($levels) : null;
    }

    /**
     * Detect the latest Android build tools version.
     */
    protected function detectAndroidBuildTools(string $sdkPath): ?string
    {
        $buildToolsDir = $sdkPath . DIRECTORY_SEPARATOR . 'build-tools';

        if (! is_dir($buildToolsDir)) {
            return null;
        }

        $versions = [];
        foreach (scandir($buildToolsDir) ?: [] as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($buildToolsDir . DIRECTORY_SEPARATOR . $dir)) {
                $versions[] = $dir;
            }
        }

        if (empty($versions)) {
            return null;
        }

        usort($versions, 'version_compare');

        return end($versions);
    }

    /**
     * Detect Xcode (macOS only).
     *
     * @return array{installed: bool, version: string|null, path: string|null}
     */
    public function detectXcode(): array
    {
        if (! $this->isMacOS()) {
            return ['installed' => false, 'version' => null, 'path' => null];
        }

        $path = $this->executeCommand('xcode-select -p 2>/dev/null');

        if (! $path) {
            return ['installed' => false, 'version' => null, 'path' => null];
        }

        $versionOutput = $this->executeCommand('xcodebuild -version 2>/dev/null');
        $version = null;

        if ($versionOutput && preg_match('/Xcode\s+([\d.]+)/', $versionOutput, $matches)) {
            $version = $matches[1];
        }

        return [
            'installed' => true,
            'version' => $version,
            'path' => trim($path),
        ];
    }

    /**
     * Detect Java installation.
     *
     * @return array{installed: bool, version: string|null, path: string|null}
     */
    public function detectJava(): array
    {
        $tool = $this->detectTool('java');

        if (! $tool['installed']) {
            // Also check JAVA_HOME
            $javaHome = getenv('JAVA_HOME');
            if ($javaHome && is_dir($javaHome)) {
                $binary = $javaHome . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'java';
                if ($this->isWindows()) {
                    $binary .= '.exe';
                }

                if (file_exists($binary)) {
                    $output = $this->executeCommand("\"{$binary}\" -version 2>&1");
                    $version = null;
                    if ($output && preg_match('/version\s+"([\d._]+)"/', $output, $matches)) {
                        $version = $matches[1];
                    }

                    return ['installed' => true, 'version' => $version, 'path' => $javaHome];
                }
            }
        }

        return $tool;
    }

    /**
     * Parse a version string from tool output.
     */
    protected function parseVersion(string $tool, string $output): ?string
    {
        $output = trim($output);

        return match ($tool) {
            'composer' => preg_match('/(\d+\.\d+\.\d+)/', $output, $m) ? $m[1] : null,
            'node' => preg_match('/v?([\d.]+)/', $output, $m) ? $m[1] : null,
            'npm', 'npx' => preg_match('/([\d.]+)/', $output, $m) ? $m[1] : null,
            'git' => preg_match('/git version ([\d.]+)/', $output, $m) ? $m[1] : null,
            'gradle' => preg_match('/Gradle ([\d.]+)/', $output, $m) ? $m[1] : null,
            'java' => preg_match('/version\s+"([\d._]+)"/', $output, $m) ? $m[1] :
                       (preg_match('/([\d.]+)/', $output, $m) ? $m[1] : null),
            default => preg_match('/([\d.]+)/', $output, $m) ? $m[1] : null,
        };
    }

    /**
     * Execute a command and return the output.
     */
    protected function executeCommand(string $command): ?string
    {
        $output = @shell_exec($command);

        return $output ? trim($output) : null;
    }

    /**
     * Get platform build availability summary.
     *
     * @return array<string, array{available: bool, reason: string|null}>
     */
    public function platformAvailability(): array
    {
        $android = $this->detectAndroidSdk();
        $java = $this->detectJava();
        $xcode = $this->detectXcode();

        return [
            'android' => [
                'available' => $android['installed'] && $java['installed'],
                'reason' => $this->androidAvailabilityReason($android, $java),
            ],
            'windows' => [
                'available' => $this->isWindows(),
                'reason' => $this->isWindows() ? null : 'Requires Windows host',
            ],
            'linux' => [
                'available' => $this->isLinux(),
                'reason' => $this->isLinux() ? null : 'Requires Linux host',
            ],
            'macos' => [
                'available' => $this->isMacOS(),
                'reason' => $this->isMacOS() ? null : 'Requires macOS host',
            ],
            'ios' => [
                'available' => $this->isMacOS() && $xcode['installed'],
                'reason' => $this->iosAvailabilityReason($xcode),
            ],
        ];
    }

    /**
     * Get the reason why Android build may not be available.
     */
    protected function androidAvailabilityReason(array $android, array $java): ?string
    {
        if (! $java['installed']) {
            return 'Java (JDK) is not installed. Install JDK 17+.';
        }

        if (! $android['installed']) {
            return 'Android SDK not found. Set ANDROID_HOME or install Android Studio.';
        }

        return null;
    }

    /**
     * Get the reason why iOS build may not be available.
     */
    protected function iosAvailabilityReason(array $xcode): ?string
    {
        if (! $this->isMacOS()) {
            return 'Requires macOS + Xcode. Build on a Mac or use CI.';
        }

        if (! $xcode['installed']) {
            return 'Xcode is not installed. Install from the Mac App Store.';
        }

        return null;
    }

    /**
     * Clear the detection cache.
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }
}
