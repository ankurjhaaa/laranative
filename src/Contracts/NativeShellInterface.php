<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Contracts;

/**
 * Native shell interface for platform-specific application shell/WebView.
 */
interface NativeShellInterface
{
    /**
     * Get the platform identifier.
     */
    public function platform(): string;

    /**
     * Initialise the native shell with a local URL.
     *
     * @param  string  $url  The local URL where Laravel is being served.
     * @param  array<string, mixed>  $options
     */
    public function initialize(string $url, array $options = []): bool;

    /**
     * Check whether a native capability is supported.
     *
     * @param  string  $capability  e.g. 'camera', 'file_picker', 'notifications'.
     */
    public function supports(string $capability): bool;

    /**
     * Get a list of all supported capabilities.
     *
     * @return array<string, bool>
     */
    public function capabilities(): array;

    /**
     * Navigate the WebView to a given URL.
     */
    public function navigate(string $url): void;

    /**
     * Execute JavaScript in the WebView context.
     *
     * @param  string  $javascript
     * @return mixed
     */
    public function evaluateJavascript(string $javascript): mixed;

    /**
     * Get the current URL displayed in the WebView.
     */
    public function currentUrl(): ?string;

    /**
     * Set the window/app title.
     */
    public function setTitle(string $title): void;

    /**
     * Set the window dimensions (desktop only).
     */
    public function setDimensions(int $width, int $height): void;
}
