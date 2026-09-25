<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Illuminate\Console\Command;

/**
 * Base command for all LaraNative Artisan commands.
 *
 * Provides shared output formatting and banner display.
 */
abstract class BaseCommand extends Command
{
    /**
     * Display the LaraNative banner.
     */
    protected function banner(): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>LaraNative</>');
        $this->line('<fg=gray>─────────────────────────────</>');
        $this->newLine();
    }

    /**
     * Display a success check.
     */
    protected function check(string $message): void
    {
        $this->line("  <fg=green>✓</> {$message}");
    }

    /**
     * Display a failure.
     */
    protected function failure(string $message): void
    {
        $this->line("  <fg=red>✗</> {$message}");
    }

    /**
     * Display a warning.
     */
    protected function warning(string $message): void
    {
        $this->line("  <fg=yellow>⚠</> {$message}");
    }

    /**
     * Display a skip message.
     */
    protected function skip(string $message): void
    {
        $this->line("  <fg=gray>⊘</> {$message}");
    }

    /**
     * Display a section header.
     */
    protected function section(string $title): void
    {
        $this->newLine();
        $this->line("<fg=white;options=bold>{$title}</>");
    }

    /**
     * Display a key-value pair.
     */
    protected function keyValue(string $key, string $value): void
    {
        $this->line("  <fg=gray>{$key}:</> {$value}");
    }
}
