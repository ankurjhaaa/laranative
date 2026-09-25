<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Core runtime interface for platform-specific PHP/Laravel runtime management.
 *
 * Each platform implementation is responsible for starting PHP, loading Laravel,
 * serving it locally, resolving paths, managing SQLite, communicating with the
 * native shell, and handling lifecycle events.
 */
interface RuntimeInterface
{
    /**
     * Get the platform identifier.
     */
    public function platform(): string;

    /**
     * Boot the runtime environment.
     *
     * Initialises the embedded PHP runtime, configures environment variables,
     * and prepares the Laravel application for local serving.
     *
     * @param  array<string, mixed>  $options
     * @return bool True if the runtime booted successfully.
     */
    public function boot(array $options = []): bool;

    /**
     * Start serving the Laravel application locally.
     *
     * @param  string  $host  Local host address (e.g. '127.0.0.1').
     * @param  int     $port  Local port number.
     * @return bool True if the server started successfully.
     */
    public function serve(string $host = '127.0.0.1', int $port = 8080): bool;

    /**
     * Stop the local Laravel server.
     */
    public function stop(): bool;

    /**
     * Shutdown the runtime and release all resources.
     */
    public function shutdown(): void;

    /**
     * Check if the runtime is currently active.
     */
    public function isRunning(): bool;

    /**
     * Resolve a platform-specific local filesystem path.
     *
     * @param  string  $relativePath  Path relative to the application root.
     * @return string Absolute path on the target platform.
     */
    public function resolvePath(string $relativePath): string;

    /**
     * Get the path to the SQLite database for this platform.
     */
    public function databasePath(): string;

    /**
     * Get the path to the embedded PHP binary for this platform.
     */
    public function phpBinaryPath(): string;

    /**
     * Get the local URL where the Laravel application is being served.
     */
    public function localUrl(): string;

    /**
     * Get the runtime configuration.
     *
     * @return array<string, mixed>
     */
    public function config(): array;

    /**
     * Handle a crash or fatal error.
     *
     * @param  \Throwable  $exception
     */
    public function handleCrash(\Throwable $exception): void;

    /**
     * Get the runtime log path.
     */
    public function logPath(): string;
}
