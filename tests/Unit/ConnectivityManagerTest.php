<?php

declare(strict_types=1);

namespace Tymiqly\LaraNative\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tymiqly\LaraNative\Native\ConnectivityManager;

class ConnectivityManagerTest extends TestCase
{
    public function test_register_and_query_service(): void
    {
        $manager = new ConnectivityManager();
        $manager->registerService('auth', 'offline');

        $this->assertSame('offline', $manager->requirementFor('auth'));
    }

    public function test_default_requirement_is_offline(): void
    {
        $manager = new ConnectivityManager();

        $this->assertSame('offline', $manager->requirementFor('unknown_service'));
    }

    public function test_services_from_constructor(): void
    {
        $manager = new ConnectivityManager([
            'auth' => 'offline',
            'payment' => 'online-required',
        ]);

        $this->assertSame('offline', $manager->requirementFor('auth'));
        $this->assertSame('online-required', $manager->requirementFor('payment'));
    }

    public function test_offline_service_can_always_operate(): void
    {
        $manager = new ConnectivityManager(['auth' => 'offline']);
        $manager->forceStatus(false);

        $this->assertTrue($manager->canOperate('auth'));
    }

    public function test_online_required_service_cannot_operate_offline(): void
    {
        $manager = new ConnectivityManager(['payment' => 'online-required']);
        $manager->forceStatus(false);

        $this->assertFalse($manager->canOperate('payment'));
    }

    public function test_online_required_service_can_operate_online(): void
    {
        $manager = new ConnectivityManager(['payment' => 'online-required']);
        $manager->forceStatus(true);

        $this->assertTrue($manager->canOperate('payment'));
    }

    public function test_force_status_overrides_detection(): void
    {
        $manager = new ConnectivityManager();

        $manager->forceStatus(true);
        $this->assertTrue($manager->isOnline());
        $this->assertSame('online', $manager->status());

        $manager->forceStatus(false);
        $this->assertFalse($manager->isOnline());
        $this->assertSame('offline', $manager->status());
    }

    public function test_clear_cache_resets_status(): void
    {
        $manager = new ConnectivityManager();
        $manager->forceStatus(true);

        $this->assertTrue($manager->isOnline());

        $manager->clearCache();
        // After clearing, it will re-detect (result depends on actual connectivity)
        $status = $manager->status();
        $this->assertContains($status, ['online', 'offline']);
    }

    public function test_all_services_listed(): void
    {
        $services = ['auth' => 'offline', 'sync' => 'online'];
        $manager = new ConnectivityManager($services);

        $this->assertSame($services, $manager->services());
    }

    public function test_invalid_requirement_throws_exception(): void
    {
        $manager = new ConnectivityManager();

        $this->expectException(\InvalidArgumentException::class);
        $manager->registerService('test', 'invalid-requirement');
    }
}
