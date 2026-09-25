<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Sync driver interface for optional data synchronization between
 * the local SQLite database and a remote Laravel API.
 */
interface SyncDriverInterface
{
    /**
     * Check if sync is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Check if the device is currently online.
     */
    public function isOnline(): bool;

    /**
     * Queue a record for synchronization.
     *
     * @param  string  $model  The Eloquent model class.
     * @param  string|int  $modelId  The model primary key.
     * @param  string  $action  One of 'create', 'update', 'delete'.
     * @param  array<string, mixed>  $data  The changed data.
     */
    public function queue(string $model, string|int $modelId, string $action, array $data = []): void;

    /**
     * Push all queued changes to the remote API.
     *
     * @return array{pushed: int, failed: int, errors: array<string>}
     */
    public function push(): array;

    /**
     * Pull changes from the remote API.
     *
     * @param  string|null  $model  Optionally limit to a specific model.
     * @return array{pulled: int, conflicts: int}
     */
    public function pull(?string $model = null): array;

    /**
     * Get the timestamp of the last successful sync.
     */
    public function lastSyncedAt(): ?\DateTimeInterface;

    /**
     * Get the number of pending changes.
     */
    public function pendingCount(): int;

    /**
     * Get the remote API base URL.
     */
    public function remoteUrl(): ?string;

    /**
     * Set the authentication token for API communication.
     */
    public function setAuthToken(string $token): void;
}
