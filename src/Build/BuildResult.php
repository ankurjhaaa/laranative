<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build;

use Tymiqly\LaraNative\Contracts\BuildResultInterface;

/**
 * Immutable build result returned after a platform build completes.
 */
class BuildResult implements BuildResultInterface
{
    /**
     * @param  array<string>  $buildWarnings
     */
    public function __construct(
        protected bool $success,
        protected ?string $artifact = null,
        protected string $statusMessage = '',
        protected float $buildDuration = 0.0,
        protected array $buildWarnings = [],
        protected ?string $errorMessage = null,
        protected ?int $fileSize = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->success;
    }

    public function artifactPath(): ?string
    {
        return $this->artifact;
    }

    public function message(): string
    {
        return $this->statusMessage;
    }

    public function duration(): float
    {
        return $this->buildDuration;
    }

    public function warnings(): array
    {
        return $this->buildWarnings;
    }

    public function error(): ?string
    {
        return $this->errorMessage;
    }

    public function artifactSize(): ?int
    {
        if ($this->fileSize !== null) {
            return $this->fileSize;
        }

        if ($this->artifact && file_exists($this->artifact)) {
            return (int) filesize($this->artifact);
        }

        return null;
    }

    /**
     * Create a successful result.
     *
     * @param  array<string>  $warnings
     */
    public static function success(string $artifactPath, string $message, float $duration, array $warnings = []): static
    {
        return new static(
            success: true,
            artifact: $artifactPath,
            statusMessage: $message,
            buildDuration: $duration,
            buildWarnings: $warnings,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(string $error, float $duration = 0.0): static
    {
        return new static(
            success: false,
            statusMessage: 'Build failed',
            buildDuration: $duration,
            errorMessage: $error,
        );
    }
}
