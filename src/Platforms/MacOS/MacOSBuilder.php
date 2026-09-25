<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\MacOS;

use Tymiqly\LaraNative\Build\BuildResult;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Platforms\AbstractPlatformBuilder;

/**
 * macOS platform builder.
 *
 * Generates a macOS application bundle (.app) with signing and notarization support.
 */
class MacOSBuilder extends AbstractPlatformBuilder
{
    public function platform(): string
    {
        return 'macos';
    }

    public function displayName(): string
    {
        return 'macOS';
    }

    public function supportedArchitectures(): array
    {
        return ['x86_64', 'arm64', 'universal'];
    }

    public function artifactExtensions(): array
    {
        return ['app', 'dmg'];
    }

    public function checkAvailability(): array
    {
        $isMac = $this->detector->isMacOS();

        return [
            'available' => $isMac,
            'reason' => $isMac ? null : 'macOS builds require a macOS host.',
            'requirements' => $isMac ? [] : ['os' => 'macOS required'],
        ];
    }

    public function validatePrerequisites(): array
    {
        return [
            [
                'check' => 'macOS Host',
                'passed' => $this->detector->isMacOS(),
                'message' => $this->detector->isMacOS()
                    ? 'Running on macOS'
                    : 'macOS host required for macOS builds',
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
            'macOS build is not yet implemented. This platform is planned for Phase 2.'
        );
    }
}
