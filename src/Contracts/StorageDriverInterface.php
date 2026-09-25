<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Storage driver interface for platform-specific file storage.
 */
interface StorageDriverInterface
{
    /**
     * Get the platform identifier.
     */
    public function platform(): string;

    /**
     * Get the base storage directory for this platform.
     */
    public function basePath(): string;

    /**
     * Get the application data directory.
     */
    public function appDataPath(): string;

    /**
     * Get the cache directory.
     */
    public function cachePath(): string;

    /**
     * Get the log directory.
     */
    public function logPath(): string;

    /**
     * Get the database directory.
     */
    public function databasePath(): string;

    /**
     * Get the user-generated files directory.
     */
    public function userFilesPath(): string;

    /**
     * Get the temporary directory.
     */
    public function tempPath(): string;

    /**
     * Ensure all required directories exist.
     */
    public function ensureDirectories(): void;

    /**
     * Resolve a path relative to the storage base.
     *
     * @param  string  $relativePath
     * @return string Absolute platform path.
     */
    public function resolve(string $relativePath): string;

    /**
     * Get the available storage space in bytes.
     */
    public function availableSpace(): ?int;
}
