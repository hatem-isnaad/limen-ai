<?php

namespace LimenAi\Tests\Unit\Providers;

use LimenAi\Providers\Concerns\OpenAiStreamingToolCallAssembler;
use LimenAi\Tests\TestCase;

class OpenAiStreamingToolCallAssemblerTest extends TestCase
{
    public function test_it_assembles_tool_call_fragments_by_index(): void
    {
        $assembler = new OpenAiStreamingToolCallAssembler();

        $assembler->ingestDeltas([
            [
                'index' => 0,
                'id' => 'call_abc',
                'function' => ['name' => 'example_echo', 'arguments' => '{"message":'],
            ],
        ]);

        $assembler->ingestDeltas([
            [
                'index' => 0,
                'function' => ['arguments' => '"hi"}'],
            ],
        ]);

        $toolCalls = $assembler->toOpenAiToolCalls();

        $this->assertCount(1, $toolCalls);
        $this->assertSame('call_abc', $toolCalls[0]['id']);
        $this->assertSame('example_echo', $toolCalls[0]['function']['name']);
        $this->assertSame('{"message":"hi"}', $toolCalls[0]['function']['arguments']);
    }
}
