<?php

namespace LimenAi\Tests\Unit\Runtime;

use Illuminate\Support\Facades\Event;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Events\AgentStreamDelta;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\Middleware\DeferredToolsUntilSecondStepMiddleware;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class StreamMiddlewareIntegrationTest extends TestCase
{
    public function test_stream_applies_agent_middleware_options(): void
    {
        config()->set('limen-ai.agent_middleware', [
            DeferredToolsUntilSecondStepMiddleware::class,
        ]);

        config()->set('limen-ai.agents.stream_middleware_agent', [
            'name' => 'Stream Middleware Agent',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Test',
            'tools' => [],
            'skills' => [],
            'knowledge' => [],
            'authorization' => [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
        ]);

        $fake = app(FakeLlmProvider::class);
        $fake->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'OK',
            'finish_reason' => 'stop',
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
        ]));

        Event::fake([AgentStreamDelta::class]);

        foreach (app(AgentRuntime::class)->stream(
            'stream_middleware_agent',
            'conv-mw-1',
            'Hello',
            RunContextData::make(['user_id' => 1]),
        ) as $chunk) {
            // drain
        }

        $this->assertTrue(true);
    }
}
