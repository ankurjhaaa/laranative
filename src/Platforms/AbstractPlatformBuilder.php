<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms;

use Tymiqly\LaraNative\Contracts\BuildContextInterface;
use Tymiqly\LaraNative\Contracts\PlatformBuilderInterface;
use Tymiqly\LaraNative\Contracts\BuildResultInterface;
use Tymiqly\LaraNative\Build\BuildResult;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Symfony\Component\Process\Process;

/**
 * Base class for platform builders with shared functionality.
 */
abstract class AbstractPlatformBuilder implements PlatformBuilderInterface
{
    public function __construct(
        protected EnvironmentDetector $detector
    ) {}

    public function outputDirectory(): string
    {
        $outputDir = config('laranative.build.output_dir', 'dist');

        return base_path($outputDir . DIRECTORY_SEPARATOR . $this->platform());
    }

    public function clean(): bool
    {
        $dir = $this->outputDirectory();

        if (! is_dir($dir)) {
            return true;
        }

        return $this->removeDirectory($dir);
    }

    /**
     * Execute a shell command and return the result.
     *
     * @return array{exit_code: int, output: string}
     */
    protected function exec(string $command, ?string $cwd = null): array
    {
        $process = Process::fromShellCommandline($command, $cwd);
        $process->setTimeout(null);
        
        $output = '';
        
        // Add a line break before streaming output
        echo PHP_EOL;
        
        $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;
            echo $buffer; // Stream to console in real-time
        });
        
        echo PHP_EOL;

        return [
            'exit_code' => $process->getExitCode(),
            'output' => trim($output),
        ];
    }

    /**
     * Recursively remove a directory.
     */
    protected function removeDirectory(string $dir): bool
    {
        if (! is_dir($dir)) {
            return true;
        }

        try {
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

            return rmdir($dir);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Copy a directory recursively.
     */
    protected function copyDir(string $source, string $destination): bool
    {
        if (! is_dir($source)) {
            return false;
        }

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $items->getSubPathname();

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getRealPath(), $target);
            }
        }

        return true;
    }
}
