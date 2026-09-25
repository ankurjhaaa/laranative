<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

use Illuminate\Support\Facades\File;

/**
 * Installs LaraNative into the current Laravel project.
 *
 * Publishes the configuration file and creates initial directories.
 */
class InstallCommand extends BaseCommand
{
    protected $signature = 'laranative:install';

    protected $description = 'Install LaraNative into the current Laravel project';

    public function handle(): int
    {
        $this->banner();
        $this->line('Installing LaraNative...');
        $this->newLine();

        // 1. Publish config
        $configSource = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'laranative.php';
        $configDest = config_path('laranative.php');

        if (File::exists($configDest)) {
            $this->warning('config/laranative.php already exists (skipped)');
        } else {
            if (File::exists($configSource)) {
                File::copy($configSource, $configDest);
                $this->check('Published config/laranative.php');
            } else {
                $this->failure('Could not find config source file');

                return self::FAILURE;
            }
        }

        // 2. Create .laranative directory
        $laraNativeDir = base_path('.laranative');
        if (! File::isDirectory($laraNativeDir)) {
            File::makeDirectory($laraNativeDir, 0755, true);
            $this->check('Created .laranative/ directory');
        } else {
            $this->warning('.laranative/ directory already exists (skipped)');
        }

        // 3. Add .laranative to .gitignore
        $gitignore = base_path('.gitignore');
        if (File::exists($gitignore)) {
            $content = File::get($gitignore);
            $entriesToAdd = ['.laranative/', 'dist/'];
            $added = false;

            foreach ($entriesToAdd as $entry) {
                if (! str_contains($content, $entry)) {
                    $content = rtrim($content) . "\n{$entry}\n";
                    $added = true;
                }
            }

            if ($added) {
                File::put($gitignore, $content);
                $this->check('Updated .gitignore');
            }
        }

        // 4. Create dist directory
        $distDir = base_path('dist');
        if (! File::isDirectory($distDir)) {
            File::makeDirectory($distDir, 0755, true);
            $this->check('Created dist/ directory');
        }

        $this->newLine();
        $this->line('<fg=green;options=bold>LaraNative installed successfully!</>');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Edit <fg=cyan>config/laranative.php</> to configure your app');
        $this->line('  2. Run <fg=cyan>php artisan laranative:setup</> to initialise');
        $this->line('  3. Run <fg=cyan>php artisan laranative:doctor</> to check your environment');
        $this->newLine();

        return self::SUCCESS;
    }
}
