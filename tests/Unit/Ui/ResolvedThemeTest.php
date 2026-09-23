<?php

namespace LimenAi\Tests\Unit\Ui;

use LimenAi\Ui\ResolvedTheme;
use PHPUnit\Framework\TestCase;

class ResolvedThemeTest extends TestCase
{
    public function test_it_exposes_theme_accessors_with_defaults(): void
    {
        $theme = new ResolvedTheme([]);

        $this->assertSame('light', $theme->mode());
        $this->assertSame('ltr', $theme->direction());
        $this->assertSame('default', $theme->preset());
        $this->assertFalse($theme->allowsModeToggle());
        $this->assertFalse($theme->usesClientColorMode());
    }

    public function test_it_reads_custom_values(): void
    {
        $theme = new ResolvedTheme([
            'mode' => 'dark',
            'direction' => 'rtl',
            'preset' => 'ocean',
            'allow_mode_toggle' => true,
            'primary' => '#000000',
        ]);

        $this->assertSame('dark', $theme->mode());
        $this->assertSame('rtl', $theme->direction());
        $this->assertSame('ocean', $theme->preset());
        $this->assertTrue($theme->allowsModeToggle());
        $this->assertTrue($theme->usesClientColorMode());
        $this->assertSame('#000000', $theme->get('primary'));
    }

    public function test_auto_mode_uses_client_color_mode(): void
    {
        $theme = new ResolvedTheme(['mode' => 'auto']);

        $this->assertTrue($theme->usesClientColorMode());
    }

    public function test_to_array_returns_raw_values(): void
    {
        $values = ['mode' => 'dark', 'primary' => '#111'];
        $theme = new ResolvedTheme($values);

        $this->assertSame($values, $theme->toArray());
    }

    public function test_css_variables_includes_theme_tokens(): void
    {
        $theme = new ResolvedTheme([
            'primary' => '#FF0000',
            'radius' => '8px',
        ]);

        $css = $theme->cssVariables();

        $this->assertStringContainsString('--limen-ai-primary: #FF0000', $css);
        $this->assertStringContainsString('--limen-ai-radius: 8px', $css);
    }

    public function test_layout_style_includes_css_variables_for_fixed_mode(): void
    {
        $theme = new ResolvedTheme([
            'mode' => 'dark',
            'primary' => '#222222',
        ]);

        $style = $theme->layoutStyle();

        $this->assertStringContainsString('--limen-ai-primary: #222222', $style);
        $this->assertStringContainsString('font-family:', $style);
    }

    public function test_layout_style_omits_color_variables_for_auto_mode(): void
    {
        $theme = new ResolvedTheme([
            'mode' => 'auto',
            'primary' => '#222222',
        ]);

        $style = $theme->layoutStyle();

        $this->assertStringNotContainsString('--limen-ai-primary', $style);
        $this->assertStringContainsString('font-family:', $style);
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $theme = new ResolvedTheme([]);

        $this->assertSame('fallback', $theme->get('missing', 'fallback'));
    }
}
