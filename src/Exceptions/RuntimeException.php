<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Exceptions;

class RuntimeException extends LaraNativeException
{
    public static function serverFailed(string $reason): static
    {
        return new static("Failed to start the local Laravel server: {$reason}");
    }

    public static function phpBinaryNotFound(string $path): static
    {
        return (new static("PHP binary not found at: {$path}"))
            ->setHint('Ensure the PHP runtime is bundled correctly.');
    }
}
