<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Build;

use Tymiqly\LaraNative\Contracts\BuildContextInterface;

/**
 * Concrete build context carrying all information needed during a platform build.
 */
class BuildContext implements BuildContextInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        protected string $appName,
        protected string $appId,
        protected string $version,
        protected string $platformName,
        protected string $mode,
        protected string $laravelBasePath,
        protected string $buildBasePath,
        protected string $outputBasePath,
        protected ?string $arch = null,
        protected array $config = [],
        protected array $options = [],
        protected ?array $signing = null,
    ) {}

    public function appName(): string
    {
        return $this->appName;
    }

    public function appId(): string
    {
        return $this->appId;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function platform(): string
    {
        return $this->platformName;
    }

    public function buildMode(): string
    {
        return $this->mode;
    }

    public function isDebug(): bool
    {
        return $this->mode === 'debug';
    }

    public function isRelease(): bool
    {
        return $this->mode === 'release';
    }

    public function architecture(): ?string
    {
        return $this->arch;
    }

    public function laravelPath(): string
    {
        return $this->laravelBasePath;
    }

    public function buildPath(): string
    {
        return $this->buildBasePath . DIRECTORY_SEPARATOR . $this->platformName;
    }

    public function outputPath(): string
    {
        return $this->outputBasePath . DIRECTORY_SEPARATOR . $this->platformName;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function options(): array
    {
        return $this->options;
    }

    public function setOption(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    public function signingConfig(): ?array
    {
        return $this->signing;
    }

    public function shouldSign(): bool
    {
        return $this->signing !== null && $this->isRelease();
    }

    /**
     * Create a BuildContext from Laravel config.
     */
    public static function fromConfig(string $platform, string $mode, string $laravelPath): static
    {
        $outputDir = config('laranative.build.output_dir', 'dist');

        return new static(
            appName: config('laranative.name', config('app.name', 'LaraNative App')),
            appId: config('laranative.id', 'com.example.app'),
            version: config('laranative.version', '1.0.0'),
            platformName: $platform,
            mode: $mode,
            laravelBasePath: $laravelPath,
            buildBasePath: $laravelPath . DIRECTORY_SEPARATOR . '.laranative' . DIRECTORY_SEPARATOR . 'build',
            outputBasePath: $laravelPath . DIRECTORY_SEPARATOR . $outputDir,
            config: config('laranative', []),
            signing: config("laranative.platforms.{$platform}.signing"),
        );
    }
}
