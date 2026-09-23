<?php

namespace LimenAi\Tests\Unit\Memory;

use LimenAi\Exceptions\MemoryPolicyException;
use LimenAi\Memory\StrictMemoryPolicy;
use LimenAi\Tests\TestCase;

class StrictMemoryPolicyTest extends TestCase
{
    public function test_it_rejects_disallowed_memory_keys_for_agent(): void
    {
        config()->set('limen-ai.agents.example.memory.allowed_keys', ['preferred_language']);

        $this->expectException(MemoryPolicyException::class);

        app(StrictMemoryPolicy::class)->assertKeyAllowed('example', 'secret_note');
    }

    public function test_it_truncates_long_memory_values(): void
    {
        config()->set('limen-ai.agents.example.memory.max_value_length', 5);

        $value = app(StrictMemoryPolicy::class)->normalizeValue('example', 'toolongvalue');

        $this->assertSame('toolo', $value);
    }

    public function test_it_filters_entries_outside_allowlist_on_retrieval(): void
    {
        config()->set('limen-ai.agents.example.memory.allowed_keys', ['preferred_language']);

        $filtered = app(StrictMemoryPolicy::class)->filterEntries('example', [
            ['key' => 'preferred_language', 'value' => 'ar'],
            ['key' => 'blocked_key', 'value' => 'x'],
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('preferred_language', $filtered[0]['key']);
    }
}
