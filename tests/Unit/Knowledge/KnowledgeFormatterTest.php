<?php

namespace LimenAi\Tests\Unit\Knowledge;

use LimenAi\Knowledge\KnowledgeFormatter;
use LimenAi\Tests\TestCase;

class KnowledgeFormatterTest extends TestCase
{
    public function test_it_formats_chunks_as_untrusted_system_message(): void
    {
        $formatter = new KnowledgeFormatter();

        $messages = $formatter->toAgentMessages([
            [
                'collection' => 'getting_started',
                'content' => 'Limen AI requires Laravel authorization for tools.',
            ],
        ]);

        $this->assertCount(1, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertStringContainsString('untrusted', $messages[0]['content']);
        $this->assertStringContainsString('[getting_started]', $messages[0]['content']);
        $this->assertStringContainsString('Laravel authorization', $messages[0]['content']);
    }

    public function test_it_returns_empty_messages_for_no_chunks(): void
    {
        $formatter = new KnowledgeFormatter();

        $this->assertSame([], $formatter->toAgentMessages([]));
    }
}
