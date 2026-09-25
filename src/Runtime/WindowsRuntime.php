<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Runtime;

class WindowsRuntime extends AbstractRuntime
{
    public function platform(): string { return 'windows'; }

    public function boot(array $options = []): bool { return true; }

    public function serve(string $host = '127.0.0.1', int $port = 8080): bool { return false; }

    public function stop(): bool { $this->running = false; return true; }

    public function shutdown(): void { $this->stop(); }

    public function resolvePath(string $relativePath): string
    {
        return $this->storage->basePath() . DIRECTORY_SEPARATOR . $relativePath;
    }

    public function phpBinaryPath(): string
    {
        return $this->storage->resolve('php' . DIRECTORY_SEPARATOR . 'php.exe');
    }
}
