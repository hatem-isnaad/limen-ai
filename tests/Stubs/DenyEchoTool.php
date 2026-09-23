<?php

namespace LimenAi\Tests\Stubs;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Tools\BaseTool;
use LimenAi\Tools\ConfigToolDefinition;

class DenyEchoTool extends BaseTool
{
    public function key(): string
    {
        return 'deny_echo';
    }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig('deny_echo', [
            'name' => 'Deny Echo',
            'description' => 'Denied for tests.',
            'class' => self::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);
    }

    public function authorize(array $input, ToolExecutionContext $context): bool
    {
        return false;
    }

    public function handle(array $input, ToolExecutionContext $context): array
    {
        return ['message' => 'should not run'];
    }
}
