<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Support\EnvironmentDoctor;
use LimenAi\Support\PersistenceConfig;
use LimenAi\Tests\DatabaseTestCase;

class EnvironmentDoctorPersistenceTest extends DatabaseTestCase
{
    public function test_it_fails_when_ui_enabled_and_persistence_is_in_memory(): void
    {
        config()->set('limen-ai.persistence.driver', PersistenceConfig::DRIVER_MEMORY);
        config()->set('limen-ai.persistence.auto_detect', false);
        config()->set('limen-ai.ui.enabled', true);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['failures'])->contains(
            fn (string $failure): bool => str_contains($failure, 'in-memory repositories'),
        ));
    }

    public function test_it_warns_when_persistence_is_auto_detected_from_migrations(): void
    {
        config()->set('limen-ai.persistence.driver', null);
        config()->set('limen-ai.persistence.auto_detect', true);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'auto-detected as database'),
        ));
    }
}
