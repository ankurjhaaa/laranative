<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Native;

use Tymiqly\LaraNative\Contracts\ConnectivityDetectorInterface;

/**
 * Manages the offline-first capability system.
 *
 * Determines whether services can operate offline, online, or require
 * online connectivity.
 */
class ConnectivityManager implements ConnectivityDetectorInterface
{
    /**
     * Registered services and their connectivity requirements.
     *
     * @var array<string, string>
     */
    protected array $services = [];

    /**
     * Cached online status.
     */
    protected ?bool $onlineCache = null;

    /**
     * @param  array<string, string>  $services  Initial services config.
     */
    public function __construct(array $services = [])
    {
        $this->services = $services;
    }

    public function isOnline(): bool
    {
        if ($this->onlineCache !== null) {
            return $this->onlineCache;
        }

        // Attempt a real connectivity check
        $this->onlineCache = $this->checkConnectivity();

        return $this->onlineCache;
    }

    public function status(): string
    {
        return $this->isOnline() ? 'online' : 'offline';
    }

    public function requirementFor(string $service): string
    {
        return $this->services[$service] ?? 'offline';
    }

    public function registerService(string $service, string $requirement): void
    {
        if (! in_array($requirement, ['offline', 'online', 'online-required'], true)) {
            throw new \InvalidArgumentException(
                "Invalid connectivity requirement '{$requirement}'. Must be: offline, online, online-required"
            );
        }

        $this->services[$service] = $requirement;
    }

    public function canOperate(string $service): bool
    {
        $requirement = $this->requirementFor($service);

        return match ($requirement) {
            'offline' => true,
            'online' => true, // Can work offline, but prefers online
            'online-required' => $this->isOnline(),
            default => true,
        };
    }

    public function services(): array
    {
        return $this->services;
    }

    /**
     * Clear the cached connectivity state.
     */
    public function clearCache(): void
    {
        $this->onlineCache = null;
    }

    /**
     * Override the online status (useful for testing or forced offline mode).
     */
    public function forceStatus(bool $online): void
    {
        $this->onlineCache = $online;
    }

    /**
     * Check actual internet connectivity.
     */
    protected function checkConnectivity(): bool
    {
        // Try to reach a reliable endpoint
        $checkUrls = [
            'https://connectivitycheck.gstatic.com/generate_204',
            'https://www.google.com',
        ];

        foreach ($checkUrls as $url) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3,
                        'method' => 'HEAD',
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                    ],
                ]);

                $headers = @get_headers($url, false, $context);

                if ($headers !== false && ! empty($headers)) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return false;
    }
}
