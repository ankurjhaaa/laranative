<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms\IOS;

use Tymiqly\LaraNative\Build\BuildResult;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Platforms\AbstractPlatformBuilder;

/**
 * iOS platform builder.
 *
 * Generates a real Xcode-compatible iOS project. Does NOT fake an IPA
 * build on unsupported environments — clearly requires macOS + Xcode.
 */
class IOSBuilder extends AbstractPlatformBuilder
{
    public function platform(): string
    {
        return 'ios';
    }

    public function displayName(): string
    {
        return 'iOS';
    }

    public function supportedArchitectures(): array
    {
        return ['arm64'];
    }

    public function artifactExtensions(): array
    {
        return ['ipa', 'xcodeproj'];
    }

    public function checkAvailability(): array
    {
        $isMac = $this->detector->isMacOS();
        $xcode = $this->detector->detectXcode();

        $available = $isMac && $xcode['installed'];
        $reason = null;
        $requirements = [];

        if (! $isMac) {
            $reason = 'iOS builds require macOS + Xcode.';
            $requirements['os'] = 'macOS required';
            $requirements['xcode'] = 'Xcode 15+ from the Mac App Store';
        } elseif (! $xcode['installed']) {
            $reason = 'Xcode is not installed.';
            $requirements['xcode'] = 'Install Xcode from the Mac App Store';
        }

        return [
            'available' => $available,
            'reason' => $reason,
            'requirements' => $requirements,
        ];
    }

    public function validatePrerequisites(): array
    {
        $xcode = $this->detector->detectXcode();

        return [
            [
                'check' => 'macOS Host',
                'passed' => $this->detector->isMacOS(),
                'message' => $this->detector->isMacOS()
                    ? 'Running on macOS'
                    : 'macOS host required for iOS builds. Build on a Mac or use CI.',
            ],
            [
                'check' => 'Xcode',
                'passed' => $xcode['installed'],
                'message' => $xcode['installed']
                    ? "Xcode {$xcode['version']} found"
                    : 'Xcode is not installed. Install from the Mac App Store.',
            ],
        ];
    }

    public function generateProject(BuildContextInterface $context): bool
    {
        // iOS Xcode project generation will be implemented in Phase 2
        return false;
    }

    public function build(BuildContextInterface $context): BuildResultInterface
    {
        if (! $this->detector->isMacOS()) {
            return BuildResult::failure(
                'iOS builds require macOS + Xcode. Please build on a macOS machine or use CI (e.g. GitHub Actions with macOS runners).'
            );
        }

        return BuildResult::failure(
            'iOS build is not yet implemented. This platform is planned for Phase 2.'
        );
    }
}
