<?php

namespace LimenAi\Tests\Unit\Memory;

use LimenAi\Contracts\Memory\MemoryRetriever;
use LimenAi\Contracts\Memory\MemoryStore;
use LimenAi\Memory\MemoryScope;
use LimenAi\Tests\TestCase;

class DefaultMemoryRetrieverTest extends TestCase
{
    public function test_it_returns_system_message_for_enabled_user_memory(): void
    {
        config()->set('limen-ai.agents.example.memory.user', true);

        app(MemoryStore::class)->put(MemoryScope::USER, 'preferred_language', 'Arabic', [
            'scope_id' => '1',
            'agent_key' => 'example',
        ]);

        $messages = app(MemoryRetriever::class)->retrieve('example', [
            'agent_key' => 'example',
            'conversation_id' => 'conv-1',
            'user_id' => 1,
        ]);

        $this->assertCount(1, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertStringContainsString('preferred_language', $messages[0]['content']);
        $this->assertStringContainsString('Arabic', $messages[0]['content']);
    }

    public function test_it_skips_memory_when_scope_is_disabled(): void
    {
        config()->set('limen-ai.agents.example.memory.user', false);
        config()->set('limen-ai.agents.example.memory.conversation', false);

        app(MemoryStore::class)->put(MemoryScope::USER, 'preferred_language', 'Arabic', [
            'scope_id' => '1',
            'agent_key' => 'example',
        ]);

        $messages = app(MemoryRetriever::class)->retrieve('example', [
            'agent_key' => 'example',
            'conversation_id' => 'conv-1',
            'user_id' => 1,
        ]);

        $this->assertSame([], $messages);
    }
}
