<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\ToolCallParser;
use LimenAi\Tests\TestCase;

class ToolCallParserTest extends TestCase
{
    public function test_it_parses_openai_style_tool_calls(): void
    {
        $response = LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_1',
                'function' => [
                    'name' => 'example_echo',
                    'arguments' => json_encode(['message' => 'hello']),
                ],
            ]],
        ]);

        $parsed = (new ToolCallParser)->parse($response);

        $this->assertCount(1, $parsed);
        $this->assertSame('example_echo', $parsed[0]['name']);
        $this->assertSame('hello', $parsed[0]['arguments']['message']);
    }
}
