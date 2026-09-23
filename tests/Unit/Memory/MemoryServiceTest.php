<?php

namespace LimenAi\Tests\Unit\Memory;

use LimenAi\Contracts\Memory\MemoryStore;
use LimenAi\Exceptions\MemoryPolicyException;
use LimenAi\Memory\MemoryScope;
use LimenAi\Memory\MemoryService;
use LimenAi\Tests\TestCase;

class MemoryServiceTest extends TestCase
{
    public function test_remember_user_delegates_to_store_with_user_scope(): void
    {
        $service = app(MemoryService::class);

        $service->rememberUser(42, 'preferred_language', 'en', 'example');

        $this->assertSame('en', $service->recall(MemoryScope::USER, '42', 'preferred_language', 'example'));
    }

    public function test_remember_conversation_delegates_to_store_with_conversation_scope(): void
    {
        $service = app(MemoryService::class);

        $service->rememberConversation('conv-99', 'timezone', 'Asia/Riyadh', 'example');

        $this->assertSame('Asia/Riyadh', $service->recall(MemoryScope::CONVERSATION, 'conv-99', 'timezone', 'example'));
    }

    public function test_remember_without_agent_key_skips_allowlist_but_stores_value(): void
    {
        config()->set('limen-ai.agents.example.memory.allowed_keys', ['preferred_language']);

        $service = app(MemoryService::class);

        $service->remember(MemoryScope::USER, '99', 'custom_key', 'value');

        $this->assertSame('value', $service->recall(MemoryScope::USER, '99', 'custom_key'));
    }

    public function test_remember_normalizes_long_string_values(): void
    {
        config()->set('limen-ai.agents.example.memory.max_value_length', 4);

        $service = app(MemoryService::class);
        $service->rememberUser(1, 'preferred_language', 'toolong', 'example');

        $this->assertSame('tool', $service->recall(MemoryScope::USER, '1', 'preferred_language', 'example'));
    }

    public function test_remember_rejects_disallowed_keys_for_agent(): void
    {
        config()->set('limen-ai.agents.example.memory.allowed_keys', ['preferred_language']);

        $this->expectException(MemoryPolicyException::class);

        app(MemoryService::class)->rememberUser(1, 'secret_note', 'x', 'example');
    }

    public function test_forget_removes_stored_memory(): void
    {
        $service = app(MemoryService::class);

        $service->rememberUser(1, 'preferred_language', 'ar', 'example');
        $service->forget(MemoryScope::USER, '1', 'preferred_language', 'example');

        $this->assertNull($service->recall(MemoryScope::USER, '1', 'preferred_language', 'example'));
    }

    public function test_recall_returns_null_for_missing_key(): void
    {
        $this->assertNull(app(MemoryService::class)->recall(MemoryScope::USER, 'missing', 'key', 'example'));
    }

    public function test_remember_preserves_non_string_values(): void
    {
        $service = app(MemoryService::class);

        $service->rememberUser(1, 'preferred_language', ['locale' => 'ar'], 'example');

        $this->assertSame(['locale' => 'ar'], $service->recall(MemoryScope::USER, '1', 'preferred_language', 'example'));
    }

    public function test_scoped_memory_is_isolated_between_users(): void
    {
        $service = app(MemoryService::class);

        $service->rememberUser(1, 'preferred_language', 'en', 'example');
        $service->rememberUser(2, 'preferred_language', 'fr', 'example');

        $this->assertSame('en', $service->recall(MemoryScope::USER, '1', 'preferred_language', 'example'));
        $this->assertSame('fr', $service->recall(MemoryScope::USER, '2', 'preferred_language', 'example'));
    }

    public function test_store_receives_agent_key_in_context(): void
    {
        $store = $this->createMock(MemoryStore::class);
        $store->expects($this->once())
            ->method('put')
            ->with(
                MemoryScope::USER,
                'preferred_language',
                'en',
                ['scope_id' => '7', 'agent_key' => 'example'],
            );

        $service = new MemoryService($store, app(\LimenAi\Memory\StrictMemoryPolicy::class));
        $service->rememberUser(7, 'preferred_language', 'en', 'example');
    }
}
