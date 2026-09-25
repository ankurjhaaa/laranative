<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Runtime;

/**
 * Development runtime using PHP's built-in server.
 *
 * Used during `php artisan laranative:dev` on the host machine.
 */
class DevRuntime extends AbstractRuntime
{
    protected ?object $process = null;

    public function platform(): string
    {
        return 'dev';
    }

    public function boot(array $options = []): bool
    {
        $this->storage->ensureDirectories();

        return true;
    }

    public function serve(string $host = '127.0.0.1', int $port = 8080): bool
    {
        $this->host = $host;
        $this->port = $port;

        $publicPath = base_path('public');
        $phpBinary = $this->phpBinaryPath();

        $command = sprintf(
            '%s -S %s:%d -t %s %s',
            escapeshellarg($phpBinary),
            $host,
            $port,
            escapeshellarg($publicPath),
            escapeshellarg($publicPath . DIRECTORY_SEPARATOR . 'index.php')
        );

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $this->logPath(), 'a'],
            2 => ['file', $this->logPath(), 'a'],
        ];

        $logDir = dirname($this->logPath());
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->process = proc_open($command, $descriptorSpec, $pipes);

        if (is_resource($this->process)) {
            $this->running = true;
            $this->serverUrl = "http://{$host}:{$port}";

            return true;
        }

        return false;
    }

    public function stop(): bool
    {
        if ($this->process && is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }

        $this->running = false;

        return true;
    }

    public function shutdown(): void
    {
        $this->stop();
    }

    public function resolvePath(string $relativePath): string
    {
        return base_path($relativePath);
    }

    public function phpBinaryPath(): string
    {
        return PHP_BINARY;
    }
}
