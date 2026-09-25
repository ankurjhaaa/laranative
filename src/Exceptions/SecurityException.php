<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Exceptions;

class SecurityException extends LaraNativeException
{
    public static function secretsExposed(string $detail): static
    {
        return (new static("Security risk detected: {$detail}"))
            ->setHint('Run `php artisan laranative:doctor` for a full security audit.');
    }

    public static function debugModeEnabled(): static
    {
        return (new static('APP_DEBUG is enabled in a release build.'))
            ->setHint('Set APP_DEBUG=false in your production .env or use --debug for a debug build.');
    }
}
