<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    public function test_changelog_documents_current_release(): void
    {
        $root = dirname(__DIR__, 2);
        $changelog = file_get_contents($root.'/CHANGELOG.md');
        $composer = json_decode(
            file_get_contents($root.'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $version = $composer['version'] ?? null;

        $this->assertIsString($changelog);
        $this->assertIsString($version);
        $this->assertStringContainsString("## [{$version}]", $changelog);
    }

    public function test_composer_declares_semver_package_version(): void
    {
        $composer = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $version = $composer['version'] ?? null;

        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function test_release_documentation_exists(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertFileExists($root.'/docs/release.md');
        $this->assertFileExists($root.'/docs/performance.md');
        $this->assertFileExists($root.'/SECURITY.md');
        $this->assertFileExists($root.'/AGENTS.md');
    }
}
