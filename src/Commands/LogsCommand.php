<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Commands;

/**
 * View LaraNative runtime logs.
 */
class LogsCommand extends BaseCommand
{
    protected $signature = 'laranative:logs
        {--tail=50 : Number of lines to show}
        {--clear : Clear the log file}';

    protected $description = 'View LaraNative runtime logs';

    public function handle(): int
    {
        $this->banner();

        $logDir = storage_path('laranative' . DIRECTORY_SEPARATOR . 'logs');
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'laranative.log';

        if ($this->option('clear')) {
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                $this->check('Log file cleared');
            } else {
                $this->skip('No log file found');
            }

            return self::SUCCESS;
        }

        if (! file_exists($logFile)) {
            $this->line('<fg=gray>No logs found. Run <fg=cyan>php artisan laranative:dev</> to generate logs.</>');

            return self::SUCCESS;
        }

        $lines = (int) $this->option('tail');
        $content = file($logFile, FILE_IGNORE_NEW_LINES);

        if (! $content || empty($content)) {
            $this->line('<fg=gray>Log file is empty.</>');

            return self::SUCCESS;
        }

        $tail = array_slice($content, -$lines);

        $this->line("Showing last {$lines} lines:");
        $this->newLine();

        foreach ($tail as $line) {
            $this->line($line);
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
