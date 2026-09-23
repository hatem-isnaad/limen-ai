<?php

namespace LimenAi\Tests\Unit\Memory;

use LimenAi\Contracts\Security\ContentSanitizer;
use LimenAi\Memory\MemoryFormatter;
use LimenAi\Tests\TestCase;

class MemoryFormatterTest extends TestCase
{
    public function test_it_returns_empty_array_for_no_entries(): void
    {
        $this->assertSame([], app(MemoryFormatter::class)->toAgentMessages([]));
    }

    public function test_it_formats_entries_as_system_message_with_bullets(): void
    {
        $messages = app(MemoryFormatter::class)->toAgentMessages([
            ['key' => 'preferred_language', 'value' => 'Arabic'],
            ['key' => 'timezone', 'value' => 'Asia/Riyadh'],
        ]);

        $this->assertCount(1, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertStringContainsString('Relevant memory:', $messages[0]['content']);
        $this->assertStringContainsString('- preferred_language: Arabic', $messages[0]['content']);
        $this->assertStringContainsString('- timezone: Asia/Riyadh', $messages[0]['content']);
    }

    public function test_it_uses_default_key_when_entry_key_missing(): void
    {
        $messages = app(MemoryFormatter::class)->toAgentMessages([
            ['value' => 'stored'],
        ]);

        $this->assertStringContainsString('- memory: stored', $messages[0]['content']);
    }

    public function test_it_stringifies_scalar_and_null_values(): void
    {
        $messages = app(MemoryFormatter::class)->toAgentMessages([
            ['key' => 'count', 'value' => 42],
            ['key' => 'enabled', 'value' => true],
            ['key' => 'empty', 'value' => null],
        ]);

        $content = $messages[0]['content'];
        $this->assertStringContainsString('- count: 42', $content);
        $this->assertStringContainsString('- enabled: 1', $content);
        $this->assertStringContainsString('- empty: ', $content);
    }

    public function test_it_json_encodes_array_values(): void
    {
        $messages = app(MemoryFormatter::class)->toAgentMessages([
            ['key' => 'prefs', 'value' => ['theme' => 'dark']],
        ]);

        $this->assertStringContainsString('{"theme":"dark"}', $messages[0]['content']);
    }

    public function test_it_passes_values_through_content_sanitizer(): void
    {
        $sanitizer = $this->createMock(ContentSanitizer::class);
        $sanitizer->expects($this->once())
            ->method('sanitize')
            ->with('unsafe')
            ->willReturn('[sanitized]');

        $formatter = new MemoryFormatter($sanitizer);
        $messages = $formatter->toAgentMessages([
            ['key' => 'note', 'value' => 'unsafe'],
        ]);

        $this->assertStringContainsString('[sanitized]', $messages[0]['content']);
    }
}
