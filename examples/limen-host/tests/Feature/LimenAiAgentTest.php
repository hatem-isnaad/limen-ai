<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Support\PersistenceConfig;
use Tests\TestCase;

/**
 * Host integration template for Limen AI.
 *
 * Copy into your Laravel app under tests/Feature/ and adjust agent/tool keys.
 */
class LimenAiAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.persistence.driver', PersistenceConfig::DRIVER_DATABASE);
        config()->set('limen-ai.ui.middleware', []);
    }

    public function test_it_creates_a_conversation_and_accepts_a_follow_up_message(): void
    {
        $this->actingAs($this->user());

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Shipment 12345 is in transit.',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'limen_3pl',
        ])->assertCreated()->json('conversation.id');

        $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Where is shipment 12345?',
        ])->assertOk()->assertJsonPath('message.content', 'Shipment 12345 is in transit.');

        $conversation = app(ConversationRepository::class)->find($conversationId);

        $this->assertNotNull($conversation);
        $this->assertSame('limen_3pl', $conversation['agent_key']);
    }

    protected function user(): object
    {
        return new class
        {
            public int $id = 1;
        };
    }
}
