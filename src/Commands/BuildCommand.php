<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Tymiqly\LaraNative\Build\BuildContext;
use Tymiqly\LaraNative\Build\BuildPipeline;
use Tymiqly\LaraNative\Build\Steps\CopyApplicationStep;
use Tymiqly\LaraNative\Build\Steps\PrepareDatabaseStep;
use Tymiqly\LaraNative\Build\Steps\PrepareEnvironmentStep;
use Tymiqly\LaraNative\Build\Steps\SecurityValidationStep;
use Tymiqly\LaraNative\Build\Steps\ValidateConfigStep;
use Tymiqly\LaraNative\Build\Steps\ValidateLaravelStep;
use Tymiqly\LaraNative\Platforms\PlatformManager;
use Tymiqly\LaraNative\Security\SecurityValidator;

/**
 * Build the application for a target platform.
 */
class BuildCommand extends BaseCommand
{
    protected $signature = 'laranative:build
        {platform? : Target platform (android, ios, windows, macos, linux)}
        {--debug : Create a debug build}
        {--release : Create a release build}
        {--arch= : Target architecture}
        {--output= : Custom output directory}
        {--clean : Clean before building}
        {--skip-build : Generate project without building}
        {--no-sign : Skip code signing}';

    protected $description = 'Build the application for a target platform';

    public function handle(
        PlatformManager $platformManager,
        SecurityValidator $securityValidator,
    ): int {
        $this->banner();

        $platform = $this->argument('platform');

        if (! $platform) {
            $this->line('Available platforms:');
            $this->newLine();

            $availability = $platformManager->availability();

            foreach ($availability as $name => $info) {
                $status = $info['available']
                    ? '<fg=green>available</>'
                    : '<fg=yellow>' . ($info['reason'] ?? 'unavailable') . '</>';

                $this->line("  {$info['platform']}: {$status}");
            }

            $this->newLine();
            $this->line('Usage: <fg=cyan>php artisan laranative:build <platform></>');
            $this->newLine();

            return self::SUCCESS;
        }

        $platform = strtolower($platform);

        // Check if platform is registered
        if (! $platformManager->has($platform)) {
            $this->failure("Unknown platform: {$platform}");
            $this->line('  Supported: ' . implode(', ', $platformManager->supportedPlatforms()));

            return self::FAILURE;
        }

        $builder = $platformManager->builder($platform);

        // Check availability
        $availability = $builder->checkAvailability();

        if (! $availability['available']) {
            $this->failure("Cannot build for {$builder->displayName()}: {$availability['reason']}");

            if (! empty($availability['requirements'])) {
                $this->newLine();
                $this->line('  Requirements:');
                foreach ($availability['requirements'] as $req => $instruction) {
                    $this->line("    {$req}: {$instruction}");
                }
            }

            return self::FAILURE;
        }

        // Determine build mode
        $mode = $this->option('release') ? 'release' : 'debug';

        $this->line("Building <fg=cyan>{$builder->displayName()}</> ({$mode})...");
        $this->newLine();

        // Create build context
        $context = BuildContext::fromConfig($platform, $mode, base_path());

        if ($this->option('arch')) {
            $context->setOption('arch', $this->option('arch'));
        }

        if ($this->option('output')) {
            $context->setOption('output', $this->option('output'));
        }

        if ($this->option('no-sign')) {
            $context->setOption('no_sign', true);
        }

        // Build pipeline
        $pipeline = new BuildPipeline();
        $pipeline->setOutputCallback(function (string $message) {
            $this->line($message);
        });

        $pipeline->addStep(new ValidateLaravelStep());
        $pipeline->addStep(new ValidateConfigStep());
        $pipeline->addStep(new SecurityValidationStep($securityValidator));
        $pipeline->addStep(new PrepareEnvironmentStep());
        $pipeline->addStep(new CopyApplicationStep());
        $pipeline->addStep(new PrepareDatabaseStep());

        if ($this->option('skip-build')) {
            // Just generate the project, don't build
            $this->line('Generating platform project (--skip-build)...');

            $generated = $builder->generateProject($context);

            if ($generated) {
                $this->newLine();
                $this->check('Platform project generated at: ' . $context->buildPath());
            } else {
                $this->failure('Failed to generate platform project.');

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        // Execute full pipeline
        $result = $pipeline->execute($context, $builder);

        $this->newLine();

        if ($result->succeeded()) {
            $this->line('<fg=green;options=bold>Build succeeded!</>');
            $this->newLine();

            if ($result->artifactPath()) {
                $this->keyValue('Output', $result->artifactPath());
            }

            $size = $result->artifactSize();
            if ($size) {
                $this->keyValue('Size', $this->formatBytes($size));
            }

            $this->keyValue('Duration', sprintf('%.1fs', $result->duration()));

            if ($result->warnings()) {
                $this->newLine();
                $this->line('Warnings:');
                foreach ($result->warnings() as $warning) {
                    $this->warning($warning);
                }
            }
        } else {
            $this->line('<fg=red;options=bold>Build failed!</>');
            $this->newLine();

            if ($result->error()) {
                $this->failure($result->error());
            }

            $this->keyValue('Duration', sprintf('%.1fs', $result->duration()));
        }

        $this->newLine();

        return $result->succeeded() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Format bytes to human readable size.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[(int) $factor] ?? 'TB');
    }
}
