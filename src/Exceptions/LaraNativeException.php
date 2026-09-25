<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Exceptions;

use RuntimeException;

/**
 * Base exception for all LaraNative errors.
 *
 * Provides developer-friendly error messages with actionable guidance.
 */
class LaraNativeException extends RuntimeException
{
    /**
     * Optional hint for resolving the error.
     */
    protected string $hint = '';

    /**
     * Optional documentation URL.
     */
    protected string $docsUrl = '';

    public function setHint(string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function getHint(): string
    {
        return $this->hint;
    }

    public function setDocsUrl(string $url): static
    {
        $this->docsUrl = $url;

        return $this;
    }

    public function getDocsUrl(): string
    {
        return $this->docsUrl;
    }

    /**
     * Format the exception for CLI output (non-debug mode).
     */
    public function toCliOutput(): string
    {
        $output = "LaraNativeException:\n{$this->getMessage()}\n";

        if ($this->hint) {
            $output .= "\n{$this->hint}\n";
        }

        if ($this->docsUrl) {
            $output .= "\nDocs: {$this->docsUrl}\n";
        }

        return $output;
    }
}
