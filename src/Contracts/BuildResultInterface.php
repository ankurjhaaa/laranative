<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Build result returned after a platform build completes.
 */
interface BuildResultInterface
{
    /**
     * Whether the build succeeded.
     */
    public function succeeded(): bool;

    /**
     * Get the path to the built artifact (APK, EXE, AppImage, etc.).
     */
    public function artifactPath(): ?string;

    /**
     * Get a human-readable status message.
     */
    public function message(): string;

    /**
     * Get the build duration in seconds.
     */
    public function duration(): float;

    /**
     * Get any warnings generated during the build.
     *
     * @return array<string>
     */
    public function warnings(): array;

    /**
     * Get the error message if the build failed.
     */
    public function error(): ?string;

    /**
     * Get the artifact file size in bytes.
     */
    public function artifactSize(): ?int;
}
