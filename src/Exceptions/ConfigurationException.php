<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Exceptions;

class ConfigurationException extends LaraNativeException
{
    public static function missing(string $key): static
    {
        return (new static("Required configuration key '{$key}' is missing."))
            ->setHint("Add '{$key}' to config/laranative.php or run: php artisan laranative:setup");
    }

    public static function invalid(string $key, string $reason): static
    {
        return new static("Configuration '{$key}' is invalid: {$reason}");
    }
}
