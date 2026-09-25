<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Platforms;

use Tymiqly\LaraNative\Contracts\PlatformBuilderInterface;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Exceptions\PlatformException;

/**
 * Manages platform builders and routes builds to the appropriate implementation.
 */
class PlatformManager
{
    /**
     * Registered platform builders.
     *
     * @var array<string, PlatformBuilderInterface>
     */
    protected array $builders = [];

    public function __construct(
        protected EnvironmentDetector $detector
    ) {}

    /**
     * Register a platform builder.
     */
    public function register(PlatformBuilderInterface $builder): void
    {
        $this->builders[$builder->platform()] = $builder;
    }

    /**
     * Get a platform builder by name.
     *
     * @throws PlatformException
     */
    public function builder(string $platform): PlatformBuilderInterface
    {
        $platform = strtolower($platform);

        if (! isset($this->builders[$platform])) {
            throw PlatformException::unsupportedPlatform($platform);
        }

        return $this->builders[$platform];
    }

    /**
     * Get all registered platforms.
     *
     * @return array<string, PlatformBuilderInterface>
     */
    public function all(): array
    {
        return $this->builders;
    }

    /**
     * Get availability status for all registered platforms.
     *
     * @return array<string, array{available: bool, reason: string|null, platform: string}>
     */
    public function availability(): array
    {
        $result = [];

        foreach ($this->builders as $name => $builder) {
            $check = $builder->checkAvailability();
            $result[$name] = [
                'available' => $check['available'],
                'reason' => $check['reason'],
                'platform' => $builder->displayName(),
            ];
        }

        return $result;
    }

    /**
     * Get only the platforms that can be built on the current host.
     *
     * @return array<string, PlatformBuilderInterface>
     */
    public function availableBuilders(): array
    {
        $available = [];

        foreach ($this->builders as $name => $builder) {
            $check = $builder->checkAvailability();
            if ($check['available']) {
                $available[$name] = $builder;
            }
        }

        return $available;
    }

    /**
     * Check if a platform is registered.
     */
    public function has(string $platform): bool
    {
        return isset($this->builders[strtolower($platform)]);
    }

    /**
     * Get supported platform names.
     *
     * @return array<string>
     */
    public function supportedPlatforms(): array
    {
        return array_keys($this->builders);
    }
}
