<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Connectivity detector for determining network availability and
 * classifying services by their connectivity requirements.
 */
interface ConnectivityDetectorInterface
{
    /**
     * Check if the device is currently connected to the internet.
     */
    public function isOnline(): bool;

    /**
     * Get the connectivity status.
     *
     * @return string One of 'offline', 'online', 'metered'.
     */
    public function status(): string;

    /**
     * Get the connectivity requirement for a given service.
     *
     * @param  string  $service  Service identifier (e.g. 'auth', 'crud', 'payment').
     * @return string One of 'offline', 'online', 'online-required'.
     */
    public function requirementFor(string $service): string;

    /**
     * Register a service with its connectivity requirement.
     *
     * @param  string  $service
     * @param  string  $requirement  One of 'offline', 'online', 'online-required'.
     */
    public function registerService(string $service, string $requirement): void;

    /**
     * Check if a given service can operate in the current connectivity state.
     *
     * @param  string  $service
     * @return bool
     */
    public function canOperate(string $service): bool;

    /**
     * Get all registered services and their requirements.
     *
     * @return array<string, string>
     */
    public function services(): array;
}
