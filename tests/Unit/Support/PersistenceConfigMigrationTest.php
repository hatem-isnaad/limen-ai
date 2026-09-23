<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Support\PersistenceConfig;
use LimenAi\Tests\DatabaseTestCase;

class PersistenceConfigMigrationTest extends DatabaseTestCase
{
    public function test_it_resolves_database_after_migrations_when_env_is_unset(): void
    {
        $this->assertSame(PersistenceConfig::DRIVER_DATABASE, PersistenceConfig::driver($this->app));
    }
}
