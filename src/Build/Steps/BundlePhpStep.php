<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build\Steps;

use Tymiqly\LaraNative\Build\BuildStep;
use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * Bundles the current PHP runtime with the native application.
 * Copies the local PHP installation to the build directory.
 */
class BundlePhpStep extends BuildStep
{
    public function name(): string
    {
        return 'Bundle PHP runtime';
    }

    public function execute(BuildContextInterface $context, callable $output): bool
    {
        if ($context->platform() !== 'windows') {
            $output("  ✓ Skipping PHP bundle for non-Windows platform (for now)");
            return true;
        }

        $phpExe = PHP_BINARY;
        
        if (empty($phpExe) || !file_exists($phpExe)) {
            $output("  ✗ Could not determine system PHP binary path.");
            return false;
        }

        $phpDir = dirname($phpExe);
        $targetPhpDir = $context->buildPath() . DIRECTORY_SEPARATOR . 'windows-project' . DIRECTORY_SEPARATOR . 'php';

        if (! is_dir($targetPhpDir)) {
            mkdir($targetPhpDir, 0755, true);
        }

        $output("  ▸ Copying PHP from {$phpDir}...");
        
        $count = $this->copyDirectory($phpDir, $targetPhpDir);
        
        $output("  ✓ Bundled {$count} PHP files for portable runtime");

        return true;
    }

    protected function copyDirectory(string $source, string $target): int
    {
        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $count = 0;
        foreach ($items as $item) {
            $dest = $target . DIRECTORY_SEPARATOR . $items->getSubPathname();

            if ($item->isDir()) {
                if (! is_dir($dest)) {
                    mkdir($dest, 0755, true);
                }
            } else {
                copy($item->getRealPath(), $dest);
                $count++;
            }
        }
        
        return $count;
    }
}
