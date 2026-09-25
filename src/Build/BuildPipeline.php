<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build;

use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Contracts\PlatformBuilderInterface;
use Tymiqly\LaraNative\Exceptions\BuildException;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

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

    /**
     * @param  callable(): void  $progressCallback
     */
    protected $progressCallback;

    public function __construct()
    {
        $this->outputCallback = function (string $message): void {
            // Default: no output
        };
        $this->progressCallback = function (): void {
            // Default: no progress
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
     * Set the progress callback.
     *
     * @param  callable(): void  $callback
     */
    public function setProgressCallback(callable $callback): static
    {
        $this->progressCallback = $callback;

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
            $totalSteps = count($this->steps) + 2; // +2 for generation and compilation
            $currentStep = 0;

            // Run all pipeline steps
            foreach ($this->steps as $step) {
                if ($step->shouldSkip($context)) {
                    info("⊘ Skipped: {$step->name()}");
                    ($this->progressCallback)();
                    continue;
                }

                $result = spin(
                    fn () => $step->execute($context, $this->outputCallback),
                    "▸ {$step->name()}..."
                );

                if (! $result) {
                    $duration = microtime(true) - $startTime;
                    return BuildResult::failure("Build step '{$step->name()}' failed.", $duration);
                }

                ($this->progressCallback)();
            }

            // Generate platform project
            $generated = spin(
                fn () => $builder->generateProject($context),
                "▸ Generating platform project..."
            );

            if (! $generated) {
                $duration = microtime(true) - $startTime;
                return BuildResult::failure('Failed to generate platform project.', $duration);
            }

            ($this->progressCallback)();

            // Build
            $buildResult = spin(
                fn () => $builder->build($context),
                "▸ Building {$builder->displayName()} application (This may take a few minutes)..."
            );
            
            $duration = microtime(true) - $startTime;

            if ($buildResult->succeeded()) {
                ($this->progressCallback)();
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
