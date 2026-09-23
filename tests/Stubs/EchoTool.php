<?php

namespace LimenAi\Tests\Stubs;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\Tool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Tools\ConfigToolDefinition;

class EchoTool implements Tool
{
    public function key(): string
    {
        return 'example_echo';
    }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig('example_echo', [
            'name' => 'Example Echo',
            'description' => 'Echoes input back for testing.',
            'class' => self::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);
    }

    public function handle(array $input, ToolExecutionContext $context): array
    {
        return [
            'message' => $input['message'],
            'user_id' => $context->userId(),
        ];
    }
}
