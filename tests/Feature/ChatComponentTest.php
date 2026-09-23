<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use LimenAi\Support\UiAssets;
use LimenAi\Tests\TestCase;

class ChatComponentTest extends TestCase
{
    public function test_chatbot_component_renders_core_markup(): void
    {
        config()->set('limen-ai.ui.enabled', true);

        $html = Blade::render('<x-limen-ai::chatbot agent="example" />');

        $this->assertStringContainsString('data-limen-ai-chat', $html);
        $this->assertStringContainsString('data-limen-ai-messages', $html);
        $this->assertStringContainsString('data-limen-ai-send', $html);
        $this->assertStringContainsString('data-limen-ai-typing', $html);
        $this->assertStringContainsString('data-ui-config', $html);
        $this->assertStringContainsString('data-mode=', $html);
        $this->assertStringContainsString('example', $html);
    }

    public function test_widget_component_renders_launcher_and_panel(): void
    {
        config()->set('limen-ai.ui.enabled', true);

        $html = Blade::render('<x-limen-ai::widget agent="example" />');

        $this->assertStringContainsString('data-limen-ai-widget', $html);
        $this->assertStringContainsString('data-limen-ai-launcher', $html);
        $this->assertStringContainsString('data-limen-ai-unread', $html);
        $this->assertStringContainsString('data-limen-ai-chat', $html);
        $this->assertStringContainsString('data-variant="embedded"', $html);
    }

    public function test_ui_assets_are_readable(): void
    {
        $this->assertStringContainsString('.limen-ai-chat', UiAssets::css());
        $this->assertStringContainsString('class LimenAiChat', UiAssets::js());
        $this->assertStringContainsString('LimenAiSoundPlayer', UiAssets::js());
        $this->assertStringContainsString('limen-ai-chat__typing', UiAssets::css());
    }
}
