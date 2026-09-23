<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use LimenAi\Tests\TestCase;

class ThemeRenderingTest extends TestCase
{
    public function test_chatbot_renders_dark_mode_attributes(): void
    {
        $html = Blade::render('<x-limen-ai::chatbot :theme="[\'mode\' => \'dark\']" />');

        $this->assertStringContainsString('data-mode="dark"', $html);
        $this->assertStringContainsString('--limen-ai-background', $html);
    }

    public function test_chatbot_renders_arabic_preset(): void
    {
        $html = Blade::render('<x-limen-ai::chatbot :theme="[\'preset\' => \'arabic\']" />');

        $this->assertStringContainsString('data-direction="rtl"', $html);
        $this->assertStringContainsString('اكتب رسالتك', $html);
        $this->assertStringContainsString('إرسال', $html);
    }

    public function test_chatbot_renders_mode_toggle_when_enabled(): void
    {
        config()->set('limen-ai.ui.theme.allow_mode_toggle', true);

        $html = Blade::render('<x-limen-ai::chatbot />');

        $this->assertStringContainsString('data-limen-ai-mode-toggle', $html);
        $this->assertStringContainsString('data-allow-mode-toggle="true"', $html);
    }

    public function test_widget_keeps_configured_position_independent_of_direction(): void
    {
        $html = Blade::render('<x-limen-ai::widget :theme="[\'preset\' => \'arabic\', \'position\' => \'bottom-right\']" />');

        $this->assertStringContainsString('data-direction="rtl"', $html);
        $this->assertStringContainsString('data-position="bottom-right"', $html);
    }

    public function test_direction_follows_env_override_independent_of_locale(): void
    {
        config()->set('limen-ai.ui.theme.direction', 'ltr');
        config()->set('limen-ai.ui.theme.preset', 'arabic');

        $html = Blade::render('<x-limen-ai::chatbot />');

        $this->assertStringContainsString('data-direction="ltr"', $html);
    }

    public function test_widget_reads_environment_theme_over_agent_persona(): void
    {
        config()->set('limen-ai.ui.theme.title', 'Env Widget Title');
        config()->set('limen-ai.ui.theme.subtitle', 'Env Widget Subtitle');
        config()->set('limen-ai.ui.theme.position', 'bottom-left');

        $html = Blade::render('<x-limen-ai::widget />');

        $this->assertStringContainsString('Env Widget Title', $html);
        $this->assertStringContainsString('Env Widget Subtitle', $html);
        $this->assertStringContainsString('data-position="bottom-left"', $html);
    }

    public function test_widget_env_direction_overrides_arabic_preset(): void
    {
        config()->set('limen-ai.ui.theme.preset', 'arabic');
        config()->set('limen-ai.ui.theme.direction', 'ltr');

        $html = Blade::render('<x-limen-ai::widget />');

        $this->assertStringContainsString('data-direction="ltr"', $html);
    }
}
