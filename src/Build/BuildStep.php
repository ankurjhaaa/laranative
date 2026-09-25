<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build;

use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * A single step in the build pipeline.
 */
abstract class BuildStep
{
    /**
     * Get the step name for display.
     */
    abstract public function name(): string;

    /**
     * Execute this build step.
     *
     * @param  BuildContextInterface  $context
     * @param  callable(string): void  $output  Callback for outputting progress messages.
     * @return bool True if the step succeeded.
     */
    abstract public function execute(BuildContextInterface $context, callable $output): bool;

    /**
     * Check if this step should be skipped.
     */
    public function shouldSkip(BuildContextInterface $context): bool
    {
        return false;
    }
}
