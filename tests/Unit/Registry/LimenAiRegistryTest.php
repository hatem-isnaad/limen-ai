<?php

namespace LimenAi\Tests\Unit\Registry;

use LimenAi\Registry\LimenAiRegistry;
use LimenAi\Tests\TestCase;

class LimenAiRegistryTest extends TestCase
{
    public function test_it_registers_tools_agents_and_faq_entries(): void
    {
        $registry = new LimenAiRegistry();

        $registry
            ->tool('lookup', \LimenAi\Tests\Stubs\EchoTool::class)
            ->agent('support', ['name' => 'Support', 'instructions' => 'Help users'])
            ->faq('support_kb', 'How do I reset my password?', 'Use the forgot password link.');

        $this->assertArrayHasKey('lookup', $registry->tools());
        $this->assertSame(\LimenAi\Tests\Stubs\EchoTool::class, $registry->tools()['lookup']['class']);
        $this->assertArrayHasKey('support', $registry->agents());
        $this->assertSame('faq', $registry->knowledgeCollections()['support_kb']['documents'][0]['type']);
    }
}
