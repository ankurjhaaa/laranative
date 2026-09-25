<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\Windows;

use Tymiqly\LaraNative\Build\BuildResult;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Platforms\AbstractPlatformBuilder;

/**
 * Windows platform builder.
 *
 * Generates a Windows application with an embedded WebView2 shell
 * and local PHP runtime.
 */
class WindowsBuilder extends AbstractPlatformBuilder
{
    public function __construct(
        EnvironmentDetector $detector,
        protected WindowsProjectGenerator $projectGenerator,
    ) {
        parent::__construct($detector);
    }

    public function platform(): string
    {
        return 'windows';
    }

    public function displayName(): string
    {
        return 'Windows';
    }

    public function supportedArchitectures(): array
    {
        return ['x86_64', 'arm64'];
    }

    public function artifactExtensions(): array
    {
        return ['exe', 'msix'];
    }

    public function checkAvailability(): array
    {
        $isWindows = $this->detector->isWindows();
        $dotnet = $this->detector->detectTool('dotnet');

        $available = $isWindows && $dotnet['installed'];
        
        $reason = null;
        $requirements = [];

        if (!$isWindows) {
            $reason = 'Windows builds require a Windows host.';
            $requirements['os'] = 'Windows 10+ required';
        } elseif (!$dotnet['installed']) {
            $reason = '.NET SDK is not installed.';
            $requirements['dotnet'] = 'Install .NET SDK 8.0+ from https://dotnet.microsoft.com/download';
        }

        return [
            'available' => $available,
            'reason' => $reason,
            'requirements' => $requirements,
        ];
    }

    public function validatePrerequisites(): array
    {
        $dotnet = $this->detector->detectTool('dotnet');
        
        return [
            [
                'check' => 'Windows Host',
                'passed' => $this->detector->isWindows(),
                'message' => $this->detector->isWindows()
                    ? 'Running on Windows'
                    : 'Windows host required for Windows builds',
            ],
            [
                'check' => '.NET SDK',
                'passed' => $dotnet['installed'],
                'message' => $dotnet['installed']
                    ? ".NET SDK found: {$dotnet['version']}"
                    : '.NET SDK not found. Install .NET SDK 8.0+.',
            ],
        ];
    }

    public function generateProject(BuildContextInterface $context): bool
    {
        return $this->projectGenerator->generate($context);
    }

    public function build(BuildContextInterface $context): BuildResultInterface
    {
        $startTime = microtime(true);
        $projectPath = $context->buildPath() . DIRECTORY_SEPARATOR . 'windows-project';

        if (! is_dir($projectPath)) {
            return BuildResult::failure(
                'Windows project not found. Run generateProject() first.',
                microtime(true) - $startTime,
            );
        }

        $configuration = $context->isRelease() ? 'Release' : 'Debug';
        
        // Build the project using dotnet publish
        $command = "dotnet publish -c {$configuration} -r win-x64 --self-contained true -p:PublishSingleFile=true -p:IncludeNativeLibrariesForSelfExtract=true -p:PublishReadyToRun=true";

        $result = $this->exec($command, $projectPath);

        if ($result['exit_code'] !== 0) {
            return BuildResult::failure(
                "Dotnet build failed (exit code {$result['exit_code']}): {$result['output']}",
                microtime(true) - $startTime,
            );
        }

        $exePath = $projectPath . "\\bin\\{$configuration}\\net8.0-windows\\win-x64\\publish\\LaraNativeApp.exe";

        if (! file_exists($exePath)) {
            return BuildResult::failure(
                'Build completed but EXE was not found in expected output directory.',
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
        $outputFile = $outputDir . DIRECTORY_SEPARATOR . $appName . $suffix . '.exe';

        copy($exePath, $outputFile);

        // Copy laravel app directory alongside the EXE so it can be run
        $laravelSource = $projectPath . DIRECTORY_SEPARATOR . 'laravel';
        $laravelDest = $outputDir . DIRECTORY_SEPARATOR . 'laravel';
        
        if (is_dir($laravelSource)) {
            $this->copyDir($laravelSource, $laravelDest);
        }

        // Copy PHP runtime alongside the EXE
        $phpSource = $projectPath . DIRECTORY_SEPARATOR . 'php';
        $phpDest = $outputDir . DIRECTORY_SEPARATOR . 'php';
        
        if (is_dir($phpSource)) {
            $this->copyDir($phpSource, $phpDest);
        }
        
        // Zip the final package for distribution
        $zipPath = $context->outputPath() . '.zip'; // e.g. dist/windows.zip
        $this->createZipArchive($outputDir, $zipPath);

        return BuildResult::success(
            artifactPath: $zipPath,
            message: "Windows EXE built and packaged into portable ZIP successfully",
            duration: microtime(true) - $startTime,
        );
    }
    
    protected function createZipArchive(string $sourceDir, string $zipPath): bool
    {
        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($sourceDir),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                return $zip->close();
            }
        }
        
        // Fallback for missing ZipArchive extension (use PowerShell on Windows)
        $this->exec("powershell Compress-Archive -Path '{$sourceDir}\\*' -DestinationPath '{$zipPath}' -Force");
        return file_exists($zipPath);
    }
}
