<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Exceptions\ToolAuthorizationException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\Stubs\DenyEchoTool;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ToolInstanceAuthorizer;
use LimenAi\Tools\ToolPipeline;

class ToolInstanceAuthorizerTest extends TestCase
{
    public function test_it_blocks_tool_when_authorize_returns_false(): void
    {
        config()->set('limen-ai.tools.deny_echo', [
            'name' => 'Deny Echo',
            'description' => 'Always denied.',
            'class' => DenyEchoTool::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);

        $this->expectException(ToolAuthorizationException::class);

        app(ToolInstanceAuthorizer::class)->authorize(
            app(ToolRepository::class)->find('deny_echo'),
            ['message' => 'nope'],
            RunContextData::make(['user_id' => 1]),
        );
    }

    public function test_pipeline_blocks_denied_tool_after_validation(): void
    {
        config()->set('limen-ai.tools.deny_echo', [
            'name' => 'Deny Echo',
            'description' => 'Always denied.',
            'class' => DenyEchoTool::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ]);

        $this->expectException(ToolAuthorizationException::class);

        app(ToolPipeline::class)->execute('deny_echo', [
            'message' => 'blocked',
        ], RunContextData::make(['user_id' => 1]));
    }
}
