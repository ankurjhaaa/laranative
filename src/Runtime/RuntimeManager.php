<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Runtime;

use Tymiqly\LaraNative\Contracts\RuntimeInterface;
use Tymiqly\LaraNative\Database\DatabasePathResolver;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Storage\LaraNativeStorage;

/**
 * Manages runtime instances and creates the appropriate runtime for the current platform.
 */
class RuntimeManager
{
    /**
     * @var array<string, RuntimeInterface>
     */
    protected array $runtimes = [];

    public function __construct(
        protected EnvironmentDetector $detector,
        protected DatabasePathResolver $pathResolver,
        protected LaraNativeStorage $storage,
    ) {}

    /**
     * Get a runtime instance for the specified platform.
     */
    public function runtime(?string $platform = null): RuntimeInterface
    {
        $platform = $platform ?? $this->detector->detectOS();

        if (! isset($this->runtimes[$platform])) {
            $this->runtimes[$platform] = $this->createRuntime($platform);
        }

        return $this->runtimes[$platform];
    }

    /**
     * Register a custom runtime implementation.
     */
    public function register(string $platform, RuntimeInterface $runtime): void
    {
        $this->runtimes[$platform] = $runtime;
    }

    /**
     * Create the appropriate runtime for a platform.
     */
    protected function createRuntime(string $platform): RuntimeInterface
    {
        return match ($platform) {
            'windows' => new WindowsRuntime($this->pathResolver, $this->storage),
            'macos' => new MacOSRuntime($this->pathResolver, $this->storage),
            'linux' => new LinuxRuntime($this->pathResolver, $this->storage),
            default => new LinuxRuntime($this->pathResolver, $this->storage),
        };
    }
}
