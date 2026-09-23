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
        $this->assertStringContainsString('مساعد', $html);
        $this->assertStringContainsString('إرسال', $html);
    }

    public function test_chatbot_renders_mode_toggle_when_enabled(): void
    {
        config()->set('limen-ai.ui.theme.allow_mode_toggle', true);

        $html = Blade::render('<x-limen-ai::chatbot />');

        $this->assertStringContainsString('data-limen-ai-mode-toggle', $html);
        $this->assertStringContainsString('data-allow-mode-toggle="true"', $html);
    }

    public function test_widget_swaps_position_for_rtl(): void
    {
        $html = Blade::render('<x-limen-ai::widget :theme="[\'preset\' => \'arabic\', \'position\' => \'bottom-right\']" />');

        $this->assertStringContainsString('data-direction="rtl"', $html);
        $this->assertStringContainsString('data-position="bottom-right"', $html);
    }
}
