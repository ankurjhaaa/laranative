<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Exceptions;

class PlatformException extends LaraNativeException
{
    public static function unsupportedPlatform(string $platform): static
    {
        return (new static("Platform '{$platform}' is not supported."))
            ->setHint('Supported platforms: android, ios, windows, macos, linux');
    }

    public static function requiresHost(string $platform, string $requiredHost): static
    {
        return (new static("Building for '{$platform}' requires {$requiredHost}."))
            ->setHint("Please build on a {$requiredHost} machine or use CI.");
    }
}
