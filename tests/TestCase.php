<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Tymiqly\LaraNative\LaraNativeServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaraNativeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
