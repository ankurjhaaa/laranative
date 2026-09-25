<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\Windows;

use Tymiqly\LaraNative\Build\BuildResult;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Platforms\AbstractPlatformBuilder;

/**
 * Windows platform builder.
 *
 * Generates a Windows application with an embedded WebView2 shell
 * and local PHP runtime.
 */
class WindowsBuilder extends AbstractPlatformBuilder
{
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

        return [
            'available' => $isWindows,
            'reason' => $isWindows ? null : 'Windows builds require a Windows host.',
            'requirements' => $isWindows ? [] : ['os' => 'Windows 10+ required'],
        ];
    }

    public function validatePrerequisites(): array
    {
        return [
            [
                'check' => 'Windows Host',
                'passed' => $this->detector->isWindows(),
                'message' => $this->detector->isWindows()
                    ? 'Running on Windows'
                    : 'Windows host required for Windows builds',
            ],
        ];
    }

    public function generateProject(BuildContextInterface $context): bool
    {
        // Windows project generation will be implemented in Phase 2
        return false;
    }

    public function build(BuildContextInterface $context): BuildResultInterface
    {
        return BuildResult::failure(
            'Windows build is not yet implemented. This platform is planned for Phase 2.'
        );
    }
}
