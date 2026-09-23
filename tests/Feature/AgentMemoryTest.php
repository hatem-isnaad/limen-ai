<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Contracts\Memory\MemoryStore;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Memory\MemoryScope;
use LimenAi\Memory\MemoryService;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class AgentMemoryTest extends TestCase
{
    public function test_it_injects_user_memory_into_agent_messages(): void
    {
        config()->set('limen-ai.agents.example.memory.user', true);

        app(MemoryService::class)->rememberUser(1, 'preferred_language', 'Arabic', 'example');

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'I will reply in Arabic.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-memory',
            'Hello',
            RunContextData::make(['user_id' => 1]),
        );

        $messages = app(FakeLlmProvider::class)->recordedCalls()[0]['messages'];
        $contents = collect($messages)->pluck('content')->implode("\n");

        $this->assertStringContainsString('preferred_language', $contents);
        $this->assertStringContainsString('Arabic', $contents);
    }

    public function test_it_injects_conversation_memory_when_enabled(): void
    {
        config()->set('limen-ai.agents.example.memory.conversation', true);

        app(MemoryStore::class)->put(MemoryScope::CONVERSATION, 'preferred_language', 'delayed shipment', [
            'scope_id' => 'conv-topic',
            'agent_key' => 'example',
        ]);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Let me help with the shipment.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-topic',
            'Any update?',
            RunContextData::make(['user_id' => 1]),
        );

        $messages = app(FakeLlmProvider::class)->recordedCalls()[0]['messages'];
        $memoryMessage = collect($messages)->first(fn (array $message): bool => str_contains((string) ($message['content'] ?? ''), 'delayed shipment'));

        $this->assertNotNull($memoryMessage);
    }
}
