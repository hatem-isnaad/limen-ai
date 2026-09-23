<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Support\PersistenceConfig;
use LimenAi\Tests\DatabaseTestCase;

class ConversationPersistenceApiTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.persistence.driver', PersistenceConfig::DRIVER_DATABASE);
        $app['config']->set('limen-ai.ui.middleware', []);
    }

    public function test_create_conversation_and_send_message_persist_across_requests(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Follow up response.',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'example',
        ])->assertCreated()->json('conversation.id');

        $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Follow up',
        ])->assertOk();

        $conversation = app(ConversationRepository::class)->find($conversationId);

        $this->assertNotNull($conversation);
        $this->assertSame('example', $conversation['agent_key']);
        $this->assertSame(1, $conversation['user_id']);
    }
}
