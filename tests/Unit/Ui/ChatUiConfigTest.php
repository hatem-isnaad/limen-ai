<?php

namespace LimenAi\Tests\Unit\Ui;

use LimenAi\Tests\TestCase;
use LimenAi\Ui\ChatUiConfig;

class ChatUiConfigTest extends TestCase
{
    public function test_it_serializes_ui_preferences_for_the_chat_client(): void
    {
        config()->set('limen-ai.ui.sounds.enabled', false);
        config()->set('limen-ai.ui.animations.duration_ms', 320);

        $json = app(ChatUiConfig::class)->toJson();

        $this->assertStringContainsString('"sounds"', $json);
        $this->assertStringContainsString('"animations"', $json);
        $this->assertStringContainsString('320', $json);
        $this->assertStringContainsString('"enabled":false', $json);
    }
}
