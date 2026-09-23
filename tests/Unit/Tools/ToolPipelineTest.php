<?php

namespace LimenAi\Tests\Unit\Tools;

use Illuminate\Support\Facades\Event;
use LimenAi\Events\ToolCompleted;
use LimenAi\Events\ToolStarted;
use LimenAi\Exceptions\ApprovalRequiredException;
use LimenAi\Exceptions\ToolNotFoundException;
use LimenAi\Exceptions\ToolValidationException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\Stubs\EchoTool;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ToolPipeline;

class ToolPipelineTest extends TestCase
{
    public function test_it_executes_configured_tool_successfully(): void
    {
        Event::fake([ToolStarted::class, ToolCompleted::class]);

        $context = RunContextData::make([
            'user_id' => 7,
            'run_id' => 'run-1',
            'conversation_id' => 'conv-1',
            'agent_key' => 'example',
        ]);

        $result = app(ToolPipeline::class)->execute('example_echo', [
            'message' => 'hello',
        ], $context);

        $this->assertFalse($result->fromCache());
        $this->assertSame('hello', $result->output()['message']);
        $this->assertSame(7, $result->output()['user_id']);

        Event::assertDispatched(ToolStarted::class);
        Event::assertDispatched(ToolCompleted::class);
    }

    public function test_it_throws_for_unknown_tool(): void
    {
        $this->expectException(ToolNotFoundException::class);

        app(ToolPipeline::class)->execute('missing', [], RunContextData::make());
    }

    public function test_it_throws_for_invalid_input(): void
    {
        $this->expectException(ToolValidationException::class);

        app(ToolPipeline::class)->execute('example_echo', [], RunContextData::make());
    }

    public function test_it_returns_cached_result_for_idempotent_retry(): void
    {
        $context = RunContextData::make([
            'metadata' => ['idempotency_key' => 'send-1'],
        ]);

        $pipeline = app(ToolPipeline::class);

        $first = $pipeline->execute('example_echo', ['message' => 'cached'], $context);
        $second = $pipeline->execute('example_echo', ['message' => 'cached'], $context);

        $this->assertFalse($first->fromCache());
        $this->assertTrue($second->fromCache());
        $this->assertSame($first->output(), $second->output());
    }

    public function test_it_requires_approval_for_confirmation_tools(): void
    {
        config()->set('limen-ai.tools.confirmation_tool', [
            'name' => 'Confirmation Tool',
            'description' => 'Needs approval.',
            'class' => EchoTool::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
            'confirmation' => true,
        ]);

        $this->expectException(ApprovalRequiredException::class);

        app(ToolPipeline::class)->execute('confirmation_tool', [
            'message' => 'approve me',
        ], RunContextData::make());
    }
}
