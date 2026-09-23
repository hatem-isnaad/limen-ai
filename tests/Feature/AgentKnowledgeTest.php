<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Contracts\Knowledge\AgentKnowledgeRetriever;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Knowledge\KnowledgeService;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class AgentKnowledgeTest extends TestCase
{
    public function test_it_injects_config_knowledge_into_agent_messages(): void
    {
        config()->set('limen-ai.knowledge.driver', 'config');

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Tools require authorization.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-knowledge',
            'Tell me about Laravel authorization for tools',
            RunContextData::make(['user_id' => 1]),
        );

        $messages = app(FakeLlmProvider::class)->recordedCalls()[0]['messages'];
        $contents = collect($messages)->pluck('content')->implode("\n");

        $this->assertStringContainsString('untrusted', $contents);
        $this->assertStringContainsString('Laravel authorization', $contents);
    }

    public function test_it_injects_vector_knowledge_when_driver_is_vector(): void
    {
        config()->set('limen-ai.knowledge.driver', 'vector');

        app(KnowledgeService::class)->upsert(
            'getting_started',
            'shipment-policy',
            'Delayed shipments must be escalated to the warehouse supervisor within 24 hours.',
        );

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'I found the escalation policy.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-vector-knowledge',
            'What is the delayed shipment escalation policy?',
            RunContextData::make(['user_id' => 1]),
        );

        $messages = app(FakeLlmProvider::class)->recordedCalls()[0]['messages'];
        $contents = collect($messages)->pluck('content')->implode("\n");

        $this->assertStringContainsString('warehouse supervisor', $contents);
    }

    public function test_null_driver_skips_knowledge_injection(): void
    {
        config()->set('limen-ai.knowledge.driver', 'null');

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'No knowledge needed.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-no-knowledge',
            'Laravel authorization for tools',
            RunContextData::make(['user_id' => 1]),
        );

        $messages = app(FakeLlmProvider::class)->recordedCalls()[0]['messages'];
        $contents = collect($messages)->pluck('content')->implode("\n");

        $this->assertStringNotContainsString('untrusted', $contents);
        $this->assertSame([], app(AgentKnowledgeRetriever::class)->retrieve('example', 'Laravel authorization'));
    }
}
