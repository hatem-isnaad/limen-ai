<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Support\PersistenceConfig;
use LimenAi\Tests\TestCase;

class PersistenceConfigTest extends TestCase
{
    public function test_it_defaults_to_memory_without_migrations(): void
    {
        $this->assertSame(PersistenceConfig::DRIVER_MEMORY, PersistenceConfig::driver($this->app));
    }

    public function test_it_falls_back_to_memory_for_invalid_drivers(): void
    {
        $this->assertSame(
            PersistenceConfig::DRIVER_MEMORY,
            PersistenceConfig::resolveDriver('redis', false, false),
        );
    }

    public function test_it_uses_configured_driver_when_set(): void
    {
        $this->assertSame(
            PersistenceConfig::DRIVER_MEMORY,
            PersistenceConfig::resolveDriver(PersistenceConfig::DRIVER_MEMORY, true, true),
        );
    }

    public function test_it_auto_detects_database_when_migrations_exist(): void
    {
        $this->assertSame(
            PersistenceConfig::DRIVER_DATABASE,
            PersistenceConfig::resolveDriver(null, true, true),
        );
    }

    public function test_it_skips_auto_detect_when_disabled(): void
    {
        $this->assertSame(
            PersistenceConfig::DRIVER_MEMORY,
            PersistenceConfig::resolveDriver(null, false, true),
        );
    }
}
