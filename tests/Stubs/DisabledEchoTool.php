<?php

namespace LimenAi\Tests\Stubs;

use LimenAi\Contracts\Enableable;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\Tool;

class DisabledEchoTool implements Enableable, Tool
{
    public function key(): string
    {
        return 'disabled_echo';
    }

    public function definition(): \LimenAi\Contracts\Tools\ToolDefinition
    {
        return \LimenAi\Tools\ConfigToolDefinition::fromConfig('disabled_echo', [
            'name' => 'Disabled Echo',
            'class' => self::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function handle(array $input, ToolExecutionContext $context): array
    {
        return ['message' => $input['message']];
    }
}
