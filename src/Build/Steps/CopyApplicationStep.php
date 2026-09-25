<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * Copies the Laravel application files to the build directory,
 * excluding development-only files.
 */
class CopyApplicationStep extends BuildStep
{
    public function name(): string
    {
        return 'Copy application files';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        $sourcePath = $context->laravelPath();
        $targetPath = $context->buildPath() . DIRECTORY_SEPARATOR . 'app';
        $excludePatterns = $context->config('build.exclude', []);
        
        // Always exclude the build directory to prevent infinite recursion
        if (!in_array('.laranative', $excludePatterns)) {
            $excludePatterns[] = '.laranative';
        }
        $excludePatterns[] = '.laranative/*';

        $count = $this->copyDirectory($sourcePath, $targetPath, $excludePatterns);

        $output("  ✓ Copied {$count} files");

        return true;
    }

    /**
     * Recursively copy a directory with exclusions.
     *
     * @param  array<string>  $excludePatterns
     */
    protected function copyDirectory(string $source, string $target, array $excludePatterns): int
    {
        $count = 0;

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $items = new \DirectoryIterator($source);

        foreach ($items as $item) {
            if ($item->isDot()) {
                continue;
            }

            $relativePath = $item->getFilename();

            if ($this->shouldExclude($relativePath, $excludePatterns)) {
                continue;
            }

            $sourceFull = $item->getRealPath();
            $targetFull = $target . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                $count += $this->copyDirectory($sourceFull, $targetFull, $excludePatterns);
            } else {
                if (! is_dir(dirname($targetFull))) {
                    mkdir(dirname($targetFull), 0755, true);
                }
                copy($sourceFull, $targetFull);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if a path should be excluded.
     *
     * @param  array<string>  $patterns
     */
    protected function shouldExclude(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // Remove wildcard suffixes for simple matching
            $cleanPattern = rtrim($pattern, '/*');

            if ($path === $cleanPattern) {
                return true;
            }

            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
