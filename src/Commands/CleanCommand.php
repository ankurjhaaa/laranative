<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Illuminate\Support\Facades\File;

/**
 * Clean build artifacts and generated files.
 */
class CleanCommand extends BaseCommand
{
    protected $signature = 'laranative:clean
        {--all : Clean all generated files including config}
        {--platform= : Clean only a specific platform}';

    protected $description = 'Clean build artifacts and generated files';

    public function handle(): int
    {
        $this->banner();
        $this->line('Cleaning...');
        $this->newLine();

        $platform = $this->option('platform');

        // Clean build directory
        $buildDir = base_path('.laranative' . DIRECTORY_SEPARATOR . 'build');
        if ($platform) {
            $buildDir .= DIRECTORY_SEPARATOR . $platform;
        }

        if (File::isDirectory($buildDir)) {
            File::deleteDirectory($buildDir);
            $this->check('Cleaned build directory');
        } else {
            $this->skip('No build directory found');
        }

        // Clean dist directory
        $distDir = base_path(config('laranative.build.output_dir', 'dist'));
        if ($platform) {
            $distDir .= DIRECTORY_SEPARATOR . $platform;
        }

        if (File::isDirectory($distDir)) {
            File::deleteDirectory($distDir);
            $this->check('Cleaned dist directory');
        } else {
            $this->skip('No dist directory found');
        }

        if ($this->option('all')) {
            $laraNativeDir = base_path('.laranative');
            if (File::isDirectory($laraNativeDir)) {
                File::deleteDirectory($laraNativeDir);
                $this->check('Cleaned .laranative directory');
            }
        }

        $this->newLine();
        $this->line('<fg=green>Clean complete!</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
