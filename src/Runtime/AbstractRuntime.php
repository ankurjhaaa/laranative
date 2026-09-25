<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Runtime;

use Tymiqly\LaraNative\Contracts\RuntimeInterface;
use Tymiqly\LaraNative\Database\DatabasePathResolver;
use Tymiqly\LaraNative\Storage\LaraNativeStorage;

/**
 * Abstract base runtime with shared functionality across platforms.
 */
abstract class AbstractRuntime implements RuntimeInterface
{
    protected bool $running = false;

    protected ?string $serverUrl = null;

    protected string $host = '127.0.0.1';

    protected int $port = 8080;

    public function __construct(
        protected DatabasePathResolver $pathResolver,
        protected LaraNativeStorage $storage,
    ) {
        $this->host = config('laranative.runtime.host', '127.0.0.1');
        $this->port = (int) config('laranative.runtime.port', 8080);
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function localUrl(): string
    {
        return "http://{$this->host}:{$this->port}";
    }

    public function databasePath(): string
    {
        return $this->pathResolver->resolve($this->platform());
    }

    public function logPath(): string
    {
        return $this->storage->logPath() . DIRECTORY_SEPARATOR . 'laranative.log';
    }

    public function config(): array
    {
        return [
            'platform' => $this->platform(),
            'host' => $this->host,
            'port' => $this->port,
            'database_path' => $this->databasePath(),
            'log_path' => $this->logPath(),
            'php_binary' => $this->phpBinaryPath(),
            'running' => $this->running,
        ];
    }

    public function handleCrash(\Throwable $exception): void
    {
        $logPath = $this->logPath();
        $dir = dirname($logPath);

        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $entry = sprintf(
            "[%s] CRASH on %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $this->platform(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        @file_put_contents($logPath, $entry, FILE_APPEND);
    }
}
