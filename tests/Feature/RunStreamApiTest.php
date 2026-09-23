<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class RunStreamApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.ui.enabled', true);
        config()->set('limen-ai.ui.middleware', []);
        config()->set('limen-ai.streaming.poll_interval_ms', 10);
        config()->set('limen-ai.streaming.chunk_chars', 8);
    }

    public function test_it_streams_completed_run_events(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Hello from streamed run.',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');

        $runId = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Hi there',
        ])->json('run_id');

        $response = $this->get("/limen-ai/runs/{$runId}/stream");

        $response->assertOk();
        $body = $response->streamedContent();

        $this->assertStringContainsString('event: status', $body);
        $this->assertStringContainsString('event: delta', $body);
        $this->assertStringContainsString('event: completed', $body);
        $this->assertStringContainsString('Hello from streamed run.', $body);
    }

    public function test_it_returns_not_found_for_missing_run_stream(): void
    {
        $this->get('/limen-ai/runs/missing-run/stream')->assertNotFound();
    }
}
