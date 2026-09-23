<?php

namespace LimenAi\Tests\Unit\Providers;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class FakeLlmProviderTest extends TestCase
{
    public function test_it_returns_default_fake_response(): void
    {
        $provider = new FakeLlmProvider;

        $response = $provider->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertSame('Fake LLM response.', $response->content());
        $this->assertSame('stop', $response->finishReason());
    }

    public function test_it_returns_queued_responses_in_order(): void
    {
        $provider = new FakeLlmProvider;
        $provider->queueResponse(LlmResponseData::fromArray(['content' => 'First']));
        $provider->queueResponse(LlmResponseData::fromArray(['content' => 'Second']));

        $this->assertSame('First', $provider->chat([])->content());
        $this->assertSame('Second', $provider->chat([])->content());
    }

    public function test_it_records_chat_calls(): void
    {
        $provider = new FakeLlmProvider;

        $provider->chat([
            ['role' => 'user', 'content' => 'Track me'],
        ], [
            ['name' => 'example_echo'],
        ]);

        $provider->assertChatCalled();
        $this->assertCount(1, $provider->recordedCalls());
        $this->assertSame('Track me', $provider->recordedCalls()[0]['messages'][0]['content']);
    }
}
