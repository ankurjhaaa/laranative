<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Build context carries all information needed during a platform build.
 */
interface BuildContextInterface
{
    /**
     * Get the application name.
     */
    public function appName(): string;

    /**
     * Get the application package/bundle identifier.
     */
    public function appId(): string;

    /**
     * Get the application version.
     */
    public function version(): string;

    /**
     * Get the target platform identifier.
     */
    public function platform(): string;

    /**
     * Get the build mode ('debug' or 'release').
     */
    public function buildMode(): string;

    /**
     * Check if this is a debug build.
     */
    public function isDebug(): bool;

    /**
     * Check if this is a release build.
     */
    public function isRelease(): bool;

    /**
     * Get the target architecture (e.g. 'arm64', 'x86_64').
     */
    public function architecture(): ?string;

    /**
     * Get the Laravel project root path.
     */
    public function laravelPath(): string;

    /**
     * Get the LaraNative build directory.
     */
    public function buildPath(): string;

    /**
     * Get the output directory for build artifacts.
     */
    public function outputPath(): string;

    /**
     * Get a configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function config(string $key, mixed $default = null): mixed;

    /**
     * Get all build options.
     *
     * @return array<string, mixed>
     */
    public function options(): array;

    /**
     * Set a build option.
     */
    public function setOption(string $key, mixed $value): void;

    /**
     * Get the signing configuration.
     *
     * @return array<string, mixed>|null
     */
    public function signingConfig(): ?array;

    /**
     * Check if signing is enabled.
     */
    public function shouldSign(): bool;
}
