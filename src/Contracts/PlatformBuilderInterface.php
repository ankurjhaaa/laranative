<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Platform builder interface for generating platform-specific projects and builds.
 */
interface PlatformBuilderInterface
{
    /**
     * Get the platform identifier (e.g. 'android', 'windows', 'linux', 'macos', 'ios').
     */
    public function platform(): string;

    /**
     * Get a human-readable platform name.
     */
    public function displayName(): string;

    /**
     * Check whether this platform can be built on the current host.
     *
     * @return array{available: bool, reason: string|null, requirements: array<string, string>}
     */
    public function checkAvailability(): array;

    /**
     * Validate all prerequisites for building this platform.
     *
     * @return array<int, array{check: string, passed: bool, message: string}>
     */
    public function validatePrerequisites(): array;

    /**
     * Generate the platform-specific project (e.g. Android Studio project, Xcode project).
     *
     * @param  BuildContextInterface  $context
     * @return bool
     */
    public function generateProject(BuildContextInterface $context): bool;

    /**
     * Build the platform application.
     *
     * @param  BuildContextInterface  $context
     * @return BuildResultInterface
     */
    public function build(BuildContextInterface $context): BuildResultInterface;

    /**
     * Clean any generated platform artifacts.
     */
    public function clean(): bool;

    /**
     * Get the default output directory for this platform.
     */
    public function outputDirectory(): string;

    /**
     * Get the list of supported architectures.
     *
     * @return array<string>
     */
    public function supportedArchitectures(): array;

    /**
     * Get file extensions for built artifacts (e.g. ['apk', 'aab']).
     *
     * @return array<string>
     */
    public function artifactExtensions(): array;
}
