<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\Linux;

use Tymiqly\LaraNative\Build\BuildResult;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Platforms\AbstractPlatformBuilder;

/**
 * Linux platform builder.
 *
 * Generates a Linux application distributable as an AppImage, .deb, or .rpm.
 */
class LinuxBuilder extends AbstractPlatformBuilder
{
    public function platform(): string
    {
        return 'linux';
    }

    public function displayName(): string
    {
        return 'Linux';
    }

    public function supportedArchitectures(): array
    {
        return ['x86_64', 'aarch64'];
    }

    public function artifactExtensions(): array
    {
        return ['AppImage', 'deb', 'rpm'];
    }

    public function checkAvailability(): array
    {
        $isLinux = $this->detector->isLinux();

        return [
            'available' => $isLinux,
            'reason' => $isLinux ? null : 'Linux builds require a Linux host.',
            'requirements' => $isLinux ? [] : ['os' => 'Linux required'],
        ];
    }

    public function validatePrerequisites(): array
    {
        return [
            [
                'check' => 'Linux Host',
                'passed' => $this->detector->isLinux(),
                'message' => $this->detector->isLinux()
                    ? 'Running on Linux'
                    : 'Linux host required for Linux builds',
            ],
        ];
    }

    public function generateProject(BuildContextInterface $context): bool
    {
        return false;
    }

    public function build(BuildContextInterface $context): BuildResultInterface
    {
        return BuildResult::failure(
            'Linux build is not yet implemented. This platform is planned for Phase 2.'
        );
    }
}
