<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * Prepares the build environment: creates build directories and sets up
 * a clean working area.
 */
class PrepareEnvironmentStep extends BuildStep
{
    public function name(): string
    {
        return 'Prepare build environment';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        $buildPath = $context->buildPath();
        $outputPath = $context->outputPath();

        // Clean if configured
        if ($context->config('build.clean_before_build', true)) {
            if (is_dir($buildPath)) {
                $this->removeDirectory($buildPath);
            }
        }

        // Create build directories
        $directories = [
            $buildPath,
            $buildPath . DIRECTORY_SEPARATOR . 'app',
            $buildPath . DIRECTORY_SEPARATOR . 'runtime',
            $buildPath . DIRECTORY_SEPARATOR . 'shell',
            $outputPath,
        ];

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                    $output("  ✗ Failed to create directory: {$dir}");

                    return false;
                }
            }
        }

        $output('  ✓ Build environment prepared');

        return true;
    }

    /**
     * Recursively remove a directory.
     */
    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dir);
    }
}
