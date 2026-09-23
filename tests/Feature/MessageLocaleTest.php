<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class MessageLocaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.ui.middleware', []);
        config()->set('limen-ai.agents.example.persona.language', 'auto');
    }

    public function test_it_persists_preferred_language_when_user_asks_for_arabic(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'مرحبا',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'example',
        ])->json('conversation.id');

        $response = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Please talk to me in Arabic',
            'locale' => 'ar',
        ]);

        $response->assertOk()->assertJsonPath('locale', 'ar');

        $conversation = app(ConversationRepository::class)->find($conversationId);
        $this->assertSame('ar', $conversation['metadata']['preferred_language']);
    }
}
