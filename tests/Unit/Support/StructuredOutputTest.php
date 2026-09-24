<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Support\StructuredOutput;
use LimenAi\Tests\TestCase;

class StructuredOutputTest extends TestCase
{
    public function test_it_builds_openai_json_schema_chat_options(): void
    {
        $options = StructuredOutput::chatOptions([
            'format' => 'json',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'score' => ['type' => 'integer'],
                ],
                'required' => ['score'],
            ],
        ], 'sales_coach');

        $this->assertSame('json_schema', $options['response_format']['type']);
        $this->assertSame('sales_coach', $options['response_format']['json_schema']['name']);
    }

    public function test_it_decodes_structured_json_content(): void
    {
        $decoded = StructuredOutput::decode('{"score":9}', [
            'format' => 'json',
        ]);

        $this->assertSame(['score' => 9], $decoded);
    }
}
