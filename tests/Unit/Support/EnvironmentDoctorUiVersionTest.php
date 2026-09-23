<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Support\EnvironmentDoctor;
use LimenAi\Tests\TestCase;

class EnvironmentDoctorUiVersionTest extends TestCase
{
    protected function tearDown(): void
    {
        $viewsPath = resource_path('views/vendor/limen-ai');

        if (is_file($viewsPath.'/VERSION')) {
            unlink($viewsPath.'/VERSION');
        }

        parent::tearDown();
    }

    public function test_it_warns_when_published_ui_version_is_stale(): void
    {
        $viewsPath = resource_path('views/vendor/limen-ai');
        if (! is_dir($viewsPath)) {
            mkdir($viewsPath, 0777, true);
        }

        file_put_contents($viewsPath.'/VERSION', '0.9.0');

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'Published Limen AI UI views'),
        ));
    }
}
