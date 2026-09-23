<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Tests\TestCase;
use LimenAi\Tools\ConfigToolDefinition;
use LimenAi\Tools\ToolSchemaBuilder;

class ToolSchemaBuilderTest extends TestCase
{
    public function test_it_builds_openai_function_schema_from_tool_definition(): void
    {
        $tool = ConfigToolDefinition::fromConfig('example_echo', [
            'name' => 'Example Echo',
            'description' => 'Echoes input back for testing.',
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);

        $schema = (new ToolSchemaBuilder)->build($tool);

        $this->assertSame('function', $schema['type']);
        $this->assertSame('example_echo', $schema['function']['name']);
        $this->assertSame('object', $schema['function']['parameters']['type']);
        $this->assertSame(['message'], $schema['function']['parameters']['required']);
        $this->assertSame('string', $schema['function']['parameters']['properties']['message']['type']);
    }
}
