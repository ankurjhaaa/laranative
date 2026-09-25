<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Exceptions;

class BuildException extends LaraNativeException
{
    public static function stepFailed(string $step, string $reason): static
    {
        return new static("Build step '{$step}' failed: {$reason}");
    }

    public static function platformUnavailable(string $platform, string $reason): static
    {
        return (new static("Cannot build for '{$platform}': {$reason}"));
    }

    public static function artifactNotFound(string $expectedPath): static
    {
        return new static("Expected build artifact was not found at: {$expectedPath}");
    }
}
