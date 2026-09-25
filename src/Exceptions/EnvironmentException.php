<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Exceptions;

class EnvironmentException extends LaraNativeException
{
    public static function toolNotFound(string $tool, string $installHint = ''): static
    {
        $exception = new static("{$tool} was not found.");

        if ($installHint) {
            $exception->setHint("Install: {$installHint}");
        }

        return $exception;
    }

    public static function unsupportedPhpVersion(string $current, string $required): static
    {
        return (new static("PHP {$current} is not supported."))
            ->setHint("Required: PHP {$required}+");
    }

    public static function unsupportedLaravelVersion(string $current, string $supported): static
    {
        return (new static("Laravel {$current} is not supported by LaraNative."))
            ->setHint("Supported versions: {$supported}");
    }

    public static function extensionMissing(string $extension): static
    {
        return (new static("Required PHP extension '{$extension}' is not installed."))
            ->setHint("Install via: sudo apt install php-{$extension} (Linux) or enable in php.ini");
    }
}
