<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\DatabaseTestCase;

class WebChatPersistenceE2ETest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.persistence.driver', null);
        $app['config']->set('limen-ai.persistence.auto_detect', true);
        $app['config']->set('limen-ai.ui.middleware', []);
    }

    public function test_web_chat_persists_conversation_and_message_history_across_requests(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Persisted assistant reply.',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'example',
        ])->assertCreated()->json('conversation.id');

        $runId = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'First message',
        ])
            ->assertOk()
            ->assertJsonPath('queued', false)
            ->json('run_id');

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertSame('Persisted assistant reply.', $run['final_message']);

        $this->getJson("/limen-ai/conversations/{$conversationId}")
            ->assertOk()
            ->assertJsonPath('conversation.id', $conversationId);

        $messages = app(MessageRepository::class)->forConversation($conversationId);

        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertTrue(collect($messages)->contains(
            fn (array $message): bool => ($message['role'] ?? '') === 'assistant'
                && str_contains((string) ($message['content'] ?? ''), 'Persisted assistant reply.'),
        ));
    }
}
