<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build;

use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Contracts\PlatformBuilderInterface;
use Tymiqly\LaraNative\Exceptions\BuildException;

/**
 * Orchestrates the full build pipeline: validation, preparation, building, and output.
 */
class BuildPipeline
{
    /**
     * @var array<BuildStep>
     */
    protected array $steps = [];

    /**
     * @var array<string>
     */
    protected array $warnings = [];

    /**
     * @param  callable(string): void  $output
     */
    protected $outputCallback;

    public function __construct()
    {
        $this->outputCallback = function (string $message): void {
            // Default: no output
        };
    }

    /**
     * Set the output callback for progress messages.
     *
     * @param  callable(string): void  $callback
     */
    public function setOutputCallback(callable $callback): static
    {
        $this->outputCallback = $callback;

        return $this;
    }

    /**
     * Add a step to the pipeline.
     */
    public function addStep(BuildStep $step): static
    {
        $this->steps[] = $step;

        return $this;
    }

    /**
     * Execute the entire build pipeline.
     *
     * @param  BuildContextInterface    $context
     * @param  PlatformBuilderInterface $builder
     * @return BuildResultInterface
     */
    public function execute(BuildContextInterface $context, PlatformBuilderInterface $builder): BuildResultInterface
    {
        $startTime = microtime(true);

        try {
            // Run all pipeline steps
            foreach ($this->steps as $step) {
                if ($step->shouldSkip($context)) {
                    ($this->outputCallback)("⊘ Skipped: {$step->name()}");
                    continue;
                }

                ($this->outputCallback)("▸ {$step->name()}...");

                $result = $step->execute($context, $this->outputCallback);

                if (! $result) {
                    $duration = microtime(true) - $startTime;

                    return BuildResult::failure(
                        "Build step '{$step->name()}' failed.",
                        $duration,
                    );
                }

                ($this->outputCallback)("✓ {$step->name()}");
            }

            // Generate platform project
            ($this->outputCallback)('▸ Generating platform project...');

            if (! $builder->generateProject($context)) {
                $duration = microtime(true) - $startTime;

                return BuildResult::failure('Failed to generate platform project.', $duration);
            }

            ($this->outputCallback)('✓ Platform project generated');

            // Build
            ($this->outputCallback)("▸ Building {$builder->displayName()} application...");

            $buildResult = $builder->build($context);
            $duration = microtime(true) - $startTime;

            if ($buildResult->succeeded()) {
                ($this->outputCallback)("✓ {$builder->displayName()} build complete");
            }

            return $buildResult;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            return BuildResult::failure($e->getMessage(), $duration);
        }
    }

    /**
     * Get the registered steps.
     *
     * @return array<BuildStep>
     */
    public function steps(): array
    {
        return $this->steps;
    }
}
