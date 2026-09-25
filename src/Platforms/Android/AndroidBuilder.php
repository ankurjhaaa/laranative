<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\Android;

use Tymiqly\LaraNative\Build\BuildResult;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Environment\Compatibility;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Platforms\AbstractPlatformBuilder;

/**
 * Android platform builder.
 *
 * Generates a real Android project with a WebView shell, bundles the Laravel
 * application and PHP runtime, and builds an APK/AAB using Gradle.
 */
class AndroidBuilder extends AbstractPlatformBuilder
{
    public function __construct(
        EnvironmentDetector $detector,
        protected AndroidProjectGenerator $projectGenerator,
    ) {
        parent::__construct($detector);
    }

    public function platform(): string
    {
        return 'android';
    }

    public function displayName(): string
    {
        return 'Android';
    }

    public function supportedArchitectures(): array
    {
        return ['arm64-v8a', 'armeabi-v7a', 'x86', 'x86_64'];
    }

    public function artifactExtensions(): array
    {
        return ['apk', 'aab'];
    }

    public function checkAvailability(): array
    {
        $android = $this->detector->detectAndroidSdk();
        $java = $this->detector->detectJava();

        $available = $android['installed'] && $java['installed'];
        $reason = null;
        $requirements = [];

        if (! $java['installed']) {
            $reason = 'Java (JDK) is not installed.';
            $requirements['java'] = 'Install JDK 17+ from https://adoptium.net or via your package manager';
        }

        if (! $android['installed']) {
            $reason = ($reason ? $reason . ' ' : '') . 'Android SDK not found.';
            $requirements['android_sdk'] = 'Install Android Studio or set ANDROID_HOME environment variable';
        }

        return [
            'available' => $available,
            'reason' => $reason,
            'requirements' => $requirements,
        ];
    }

    public function validatePrerequisites(): array
    {
        $results = [];
        $android = $this->detector->detectAndroidSdk();
        $java = $this->detector->detectJava();

        // Java/JDK
        $results[] = [
            'check' => 'Java (JDK)',
            'passed' => $java['installed'],
            'message' => $java['installed']
                ? "Java found: {$java['version']}"
                : 'Java (JDK) is not installed. Install JDK ' . Compatibility::MIN_JDK_VERSION . '+',
        ];

        // Android SDK
        $results[] = [
            'check' => 'Android SDK',
            'passed' => $android['installed'],
            'message' => $android['installed']
                ? "Android SDK found at: {$android['path']}"
                : 'Android SDK not found. Set ANDROID_HOME or install Android Studio.',
        ];

        // API level
        if ($android['installed']) {
            $hasMinApi = $android['api_level'] !== null && $android['api_level'] >= Compatibility::MIN_ANDROID_COMPILE_SDK;
            $results[] = [
                'check' => 'Android API Level',
                'passed' => $hasMinApi,
                'message' => $hasMinApi
                    ? "API level {$android['api_level']} available"
                    : 'API level ' . Compatibility::MIN_ANDROID_COMPILE_SDK . '+ required. Run: sdkmanager "platforms;android-' . Compatibility::TARGET_ANDROID_SDK . '"',
            ];

            // Build tools
            $results[] = [
                'check' => 'Android Build Tools',
                'passed' => $android['build_tools'] !== null,
                'message' => $android['build_tools']
                    ? "Build tools {$android['build_tools']} found"
                    : 'Android build tools not found. Run: sdkmanager "build-tools;' . Compatibility::TARGET_ANDROID_SDK . '.0.0"',
            ];
        }

        // Gradle (optional — project includes wrapper)
        $gradle = $this->detector->detectTool('gradle');
        $results[] = [
            'check' => 'Gradle',
            'passed' => true, // Not required - gradlew is bundled
            'message' => $gradle['installed']
                ? "Gradle {$gradle['version']} found (project will use bundled wrapper)"
                : 'System Gradle not found (not required — project uses bundled gradlew)',
        ];

        return $results;
    }

    public function generateProject(BuildContextInterface $context): bool
    {
        return $this->projectGenerator->generate($context);
    }

    public function build(BuildContextInterface $context): BuildResultInterface
    {
        $startTime = microtime(true);

        $projectPath = $context->buildPath() . DIRECTORY_SEPARATOR . 'android-project';

        if (! is_dir($projectPath)) {
            return BuildResult::failure(
                'Android project not found. Run generateProject() first.',
                microtime(true) - $startTime,
            );
        }

        // Determine gradle command
        $gradlew = $projectPath . DIRECTORY_SEPARATOR;
        $gradlew .= PHP_OS_FAMILY === 'Windows' ? 'gradlew.bat' : 'gradlew';

        if (! file_exists($gradlew)) {
            return BuildResult::failure(
                'Gradle wrapper not found in Android project.',
                microtime(true) - $startTime,
            );
        }

        // Make gradlew executable on Unix
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($gradlew, 0755);
        }

        // Build command
        $task = $context->isRelease() ? 'assembleRelease' : 'assembleDebug';
        $command = "\"{$gradlew}\" {$task} --no-daemon";

        $result = $this->exec($command, $projectPath);

        if ($result['exit_code'] !== 0) {
            return BuildResult::failure(
                "Gradle build failed (exit code {$result['exit_code']}): {$result['output']}",
                microtime(true) - $startTime,
            );
        }

        // Find the built APK
        $apkPath = $this->findApk($projectPath, $context->isRelease());

        if (! $apkPath) {
            return BuildResult::failure(
                'Build completed but APK was not found in expected output directories.',
                microtime(true) - $startTime,
            );
        }

        // Copy to output directory
        $outputDir = $context->outputPath();

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $appName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $context->appName()) ?: 'app';
        $suffix = $context->isRelease() ? '-release' : '-debug';
        $outputFile = $outputDir . DIRECTORY_SEPARATOR . $appName . $suffix . '.apk';

        copy($apkPath, $outputFile);

        return BuildResult::success(
            artifactPath: $outputFile,
            message: "Android APK built successfully",
            duration: microtime(true) - $startTime,
        );
    }

    /**
     * Find the built APK in Gradle's output directories.
     */
    protected function findApk(string $projectPath, bool $release): ?string
    {
        $buildType = $release ? 'release' : 'debug';
        $searchPaths = [
            $projectPath . "/app/build/outputs/apk/{$buildType}",
            $projectPath . "/app/build/outputs/apk",
        ];

        foreach ($searchPaths as $searchPath) {
            if (! is_dir($searchPath)) {
                continue;
            }

            $files = glob($searchPath . '/*.apk');

            if ($files && ! empty($files)) {
                return $files[0];
            }
        }

        return null;
    }
}
