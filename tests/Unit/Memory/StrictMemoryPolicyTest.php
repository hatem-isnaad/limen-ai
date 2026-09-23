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

    public function test_it_rejects_keys_that_fail_pattern_validation(): void
    {
        config()->set('limen-ai.memory.strict.allowed_key_pattern', '/^[a-z][a-z0-9_]*$/');

        $this->expectException(MemoryPolicyException::class);
        $this->expectExceptionMessage('Memory key format is not allowed');

        app(StrictMemoryPolicy::class)->assertKeyAllowed(null, 'Invalid-Key');
    }

    public function test_it_rejects_keys_exceeding_max_length(): void
    {
        config()->set('limen-ai.memory.strict.max_key_length', 5);

        $this->expectException(MemoryPolicyException::class);
        $this->expectExceptionMessage('Memory key exceeds maximum length');

        app(StrictMemoryPolicy::class)->assertKeyAllowed(null, 'toolongkey');
    }

    public function test_it_skips_strict_checks_when_allowlist_enforcement_disabled(): void
    {
        config()->set('limen-ai.memory.strict.enforce_allowlist', false);
        config()->set('limen-ai.agents.example.memory.allowed_keys', ['preferred_language']);

        app(StrictMemoryPolicy::class)->assertKeyAllowed('example', 'anything_goes');

        $this->addToAssertionCount(1);
    }

    public function test_it_allows_any_key_when_agent_has_empty_allowlist(): void
    {
        config()->set('limen-ai.agents.example.memory.allowed_keys', []);

        app(StrictMemoryPolicy::class)->assertKeyAllowed('example', 'custom_key');

        $this->addToAssertionCount(1);
    }

    public function test_it_allows_any_key_for_unknown_agent(): void
    {
        app(StrictMemoryPolicy::class)->assertKeyAllowed('missing_agent', 'custom_key');

        $this->addToAssertionCount(1);
    }

    public function test_it_leaves_non_string_values_untouched(): void
    {
        $value = ['nested' => true];

        $this->assertSame($value, app(StrictMemoryPolicy::class)->normalizeValue('example', $value));
    }

    public function test_it_resolves_memory_limit_from_agent_config(): void
    {
        config()->set('limen-ai.agents.example.memory.limit', 7);

        $this->assertSame(7, app(StrictMemoryPolicy::class)->resolveMemoryLimit('example'));
    }

    public function test_it_falls_back_to_global_memory_limit(): void
    {
        config()->set('limen-ai.memory.limit', 15);

        $this->assertSame(15, app(StrictMemoryPolicy::class)->resolveMemoryLimit('missing'));
    }

    public function test_filter_entries_skips_invalid_entries_and_empty_keys(): void
    {
        $filtered = app(StrictMemoryPolicy::class)->filterEntries('example', [
            'not-an-array',
            ['value' => 'no-key'],
            ['key' => '', 'value' => 'empty-key'],
            ['key' => 'preferred_language', 'value' => 'ar'],
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('preferred_language', $filtered[0]['key']);
    }

    public function test_filter_entries_normalizes_values(): void
    {
        config()->set('limen-ai.agents.example.memory.max_value_length', 3);

        $filtered = app(StrictMemoryPolicy::class)->filterEntries('example', [
            ['key' => 'preferred_language', 'value' => 'toolong'],
        ]);

        $this->assertSame('too', $filtered[0]['value']);
    }

    public function test_filter_entries_returns_empty_for_empty_input(): void
    {
        $this->assertSame([], app(StrictMemoryPolicy::class)->filterEntries('example', []));
    }
}
