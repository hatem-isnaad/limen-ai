<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Exceptions\ToolValidationException;
use LimenAi\Tools\ConfigToolDefinition;
use LimenAi\Tools\ToolInputValidator;
use LimenAi\Tests\TestCase;

class ToolInputValidatorTest extends TestCase
{
    public function test_it_validates_required_fields(): void
    {
        $tool = ConfigToolDefinition::fromConfig('example_echo', [
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);

        $validated = app(ToolInputValidator::class)->validate($tool, [
            'message' => 'hello',
        ]);

        $this->assertSame(['message' => 'hello'], $validated);
    }

    public function test_it_throws_for_invalid_input(): void
    {
        $tool = ConfigToolDefinition::fromConfig('example_echo', [
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);

        $this->expectException(ToolValidationException::class);

        app(ToolInputValidator::class)->validate($tool, []);
    }
}
