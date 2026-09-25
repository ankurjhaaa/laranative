<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative;

use Illuminate\Support\ServiceProvider;
use Tymiqly\LaraNative\Commands\BuildCommand;
use Tymiqly\LaraNative\Commands\CleanCommand;
use Tymiqly\LaraNative\Commands\DevCommand;
use Tymiqly\LaraNative\Commands\DoctorCommand;
use Tymiqly\LaraNative\Commands\InstallCommand;
use Tymiqly\LaraNative\Commands\LogsCommand;
use Tymiqly\LaraNative\Commands\SetupCommand;
use Tymiqly\LaraNative\Commands\StatusCommand;
use Tymiqly\LaraNative\Contracts\ConnectivityDetectorInterface;
use Tymiqly\LaraNative\Contracts\StorageDriverInterface;
use Tymiqly\LaraNative\Database\DatabasePathResolver;
use Tymiqly\LaraNative\Database\SQLiteManager;
use Tymiqly\LaraNative\Environment\Compatibility;
use Tymiqly\LaraNative\Environment\EnvironmentDetector;
use Tymiqly\LaraNative\Native\ConnectivityManager;
use Tymiqly\LaraNative\Platforms\Android\AndroidBuilder;
use Tymiqly\LaraNative\Platforms\Android\AndroidProjectGenerator;
use Tymiqly\LaraNative\Platforms\IOS\IOSBuilder;
use Tymiqly\LaraNative\Platforms\Linux\LinuxBuilder;
use Tymiqly\LaraNative\Platforms\MacOS\MacOSBuilder;
use Tymiqly\LaraNative\Platforms\PlatformManager;
use Tymiqly\LaraNative\Platforms\Windows\WindowsBuilder;
use Tymiqly\LaraNative\Platforms\Windows\WindowsProjectGenerator;
use Tymiqly\LaraNative\Runtime\RuntimeManager;
use Tymiqly\LaraNative\Security\SecurityValidator;
use Tymiqly\LaraNative\Storage\LaraNativeStorage;

class LaraNativeServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laranative.php',
            'laranative'
        );

        // Core services
        $this->app->singleton(EnvironmentDetector::class);
        $this->app->singleton(Compatibility::class);
        $this->app->singleton(SecurityValidator::class);

        // Database
        $this->app->singleton(DatabasePathResolver::class, function ($app) {
            return new DatabasePathResolver($app->make(EnvironmentDetector::class));
        });

        $this->app->singleton(SQLiteManager::class, function ($app) {
            return new SQLiteManager($app->make(DatabasePathResolver::class));
        });

        // Storage
        $this->app->singleton(LaraNativeStorage::class, function ($app) {
            return new LaraNativeStorage($app->make(EnvironmentDetector::class));
        });

        $this->app->bind(StorageDriverInterface::class, LaraNativeStorage::class);

        // Connectivity
        $this->app->singleton(ConnectivityManager::class, function ($app) {
            $services = config('laranative.services', []);

            return new ConnectivityManager($services);
        });

        $this->app->bind(ConnectivityDetectorInterface::class, ConnectivityManager::class);

        // Runtime
        $this->app->singleton(RuntimeManager::class, function ($app) {
            return new RuntimeManager(
                $app->make(EnvironmentDetector::class),
                $app->make(DatabasePathResolver::class),
                $app->make(LaraNativeStorage::class),
            );
        });

        // Platform builders
        $this->app->singleton(AndroidProjectGenerator::class);

        $this->app->singleton(PlatformManager::class, function ($app) {
            $detector = $app->make(EnvironmentDetector::class);
            $manager = new PlatformManager($detector);

            // Register all platform builders
            $manager->register(new AndroidBuilder($detector, $app->make(AndroidProjectGenerator::class)));
            $manager->register(new WindowsBuilder($detector, $app->make(WindowsProjectGenerator::class)));
            $manager->register(new LinuxBuilder($detector));
            $manager->register(new MacOSBuilder($detector));
            $manager->register(new IOSBuilder($detector));

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laranative.php' => config_path('laranative.php'),
            ], 'laranative-config');

            $this->commands([
                InstallCommand::class,
                SetupCommand::class,
                DoctorCommand::class,
                StatusCommand::class,
                DevCommand::class,
                BuildCommand::class,
                CleanCommand::class,
                LogsCommand::class,
            ]);
        }
    }
}
