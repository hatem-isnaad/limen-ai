<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class ProtocolStreamApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.agents.protocol_agent', [
            'name' => 'Protocol Agent',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Reply briefly.',
            'tools' => [],
            'skills' => [],
            'knowledge' => [],
            'authorization' => [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
        ]);
        config()->set('limen-ai.streaming.allow_with_tools', true);
    }

    public function test_vercel_chat_stream_returns_ui_message_events(): void
    {

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Protocol hello.',
            'finish_reason' => 'stop',
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3],
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'protocol_agent',
        ])->json('conversation.id');

        $response = $this->call(
            'POST',
            "/limen-ai/conversations/{$conversationId}/chat",
            ['messages' => [['role' => 'user', 'content' => 'Hi']]],
            [],
            [],
            ['HTTP_ACCEPT' => 'text/event-stream'],
        );

        $response->assertOk();
        $body = $response->streamedContent();

        $this->assertStringContainsString('"type":"start"', $body);
        $this->assertStringContainsString('"type":"text-delta"', $body);
        $this->assertStringContainsString('"type":"finish"', $body);
    }

    public function test_ag_ui_stream_returns_run_events(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'AG-UI hello.',
            'finish_reason' => 'stop',
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3],
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'protocol_agent',
        ])->json('conversation.id');

        $response = $this->call(
            'POST',
            "/limen-ai/conversations/{$conversationId}/ag-ui",
            ['message' => 'Hi'],
            [],
            [],
            ['HTTP_ACCEPT' => 'text/event-stream'],
        );

        $response->assertOk();
        $body = $response->streamedContent();

        $this->assertStringContainsString('"type":"RUN_STARTED"', $body);
        $this->assertStringContainsString('"type":"TEXT_MESSAGE_CONTENT"', $body);
        $this->assertStringContainsString('"type":"RUN_FINISHED"', $body);
    }
}
