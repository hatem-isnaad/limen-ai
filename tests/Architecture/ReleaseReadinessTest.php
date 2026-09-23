<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    public function test_changelog_documents_v1_release(): void
    {
        $changelog = file_get_contents(dirname(__DIR__, 2).'/CHANGELOG.md');

        $this->assertIsString($changelog);
        $this->assertStringContainsString('## [1.0.0]', $changelog);
    }

    public function test_composer_declares_package_version(): void
    {
        $composer = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('1.0.0', $composer['version'] ?? null);
    }

    public function test_release_documentation_exists(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertFileExists($root.'/docs/release.md');
        $this->assertFileExists($root.'/docs/performance.md');
        $this->assertFileExists($root.'/SECURITY.md');
    }
}
