<?php

namespace LimenAi\Tests\Unit\Ui;

use LimenAi\Ui\ThemeResolver;
use LimenAi\Tests\TestCase;

class ThemeResolverTest extends TestCase
{
    public function test_it_resolves_default_light_palette(): void
    {
        $theme = app(ThemeResolver::class)->resolve();

        $this->assertSame('default', $theme->preset());
        $this->assertSame('light', $theme->mode());
        $this->assertSame('ltr', $theme->direction());
        $this->assertSame('#4F46E5', $theme->get('primary'));
        $this->assertStringContainsString('--limen-ai-primary', $theme->cssVariables());
    }

    public function test_it_applies_dark_palette_when_mode_is_dark(): void
    {
        config()->set('limen-ai.ui.theme.mode', 'dark');

        $theme = app(ThemeResolver::class)->resolve();

        $this->assertSame('#818CF8', $theme->get('primary'));
        $this->assertSame('#0F172A', $theme->get('background'));
        $this->assertFalse($theme->usesClientColorMode());
    }

    public function test_it_applies_arabic_preset_with_rtl_direction(): void
    {
        $theme = app(ThemeResolver::class)->resolve(['preset' => 'arabic']);

        $this->assertSame('rtl', $theme->direction());
        $this->assertStringContainsString('Noto Sans Arabic', (string) $theme->get('font_family'));
        $this->assertStringContainsString('مساعد', (string) $theme->get('title'));
    }

    public function test_it_merges_component_overrides(): void
    {
        $theme = app(ThemeResolver::class)->resolve([
            'primary' => '#FF0000',
            'title' => 'Custom Assistant',
        ]);

        $this->assertSame('#FF0000', $theme->get('primary'));
        $this->assertSame('Custom Assistant', $theme->get('title'));
    }

    public function test_auto_mode_defers_colors_to_client(): void
    {
        config()->set('limen-ai.ui.theme.mode', 'auto');

        $theme = app(ThemeResolver::class)->resolve();

        $this->assertTrue($theme->usesClientColorMode());
        $this->assertStringNotContainsString('--limen-ai-primary', $theme->layoutStyle());
    }

    public function test_mode_toggle_defers_colors_to_client(): void
    {
        config()->set('limen-ai.ui.theme.allow_mode_toggle', true);

        $theme = app(ThemeResolver::class)->resolve();

        $this->assertTrue($theme->allowsModeToggle());
        $this->assertTrue($theme->usesClientColorMode());
    }
}
