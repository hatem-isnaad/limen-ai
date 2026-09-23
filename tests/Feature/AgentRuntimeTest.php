<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentStarted;
use LimenAi\Exceptions\RuntimeLimitExceededException;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\TestCase;

class AgentRuntimeTest extends TestCase
{
    public function test_it_completes_single_turn_run(): void
    {
        Event::fake([AgentStarted::class, AgentCompleted::class]);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Hello from the agent.',
            'finish_reason' => 'stop',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-1',
            'Hi there',
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertSame('Hello from the agent.', $run['final_message']);

        Event::assertDispatched(AgentStarted::class);
        Event::assertDispatched(AgentCompleted::class);
    }

    public function test_it_executes_tool_calls_and_completes_run(): void
    {
        $fake = app(FakeLlmProvider::class);
        $fake->queueResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_1',
                'function' => [
                    'name' => 'example_echo',
                    'arguments' => json_encode(['message' => 'shipment status']),
                ],
            ]],
            'finish_reason' => 'tool_calls',
        ]));
        $fake->queueResponse(LlmResponseData::fromArray([
            'content' => 'The tool returned shipment status.',
            'finish_reason' => 'stop',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-1',
            'Check shipment',
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertSame('The tool returned shipment status.', $run['final_message']);
        $this->assertSame(1, $run['tool_call_count']);
    }

    public function test_it_fails_when_max_steps_are_exceeded(): void
    {
        config()->set('limen-ai.agents.example.limits.max_steps', 1);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_1',
                'function' => [
                    'name' => 'example_echo',
                    'arguments' => json_encode(['message' => 'loop']),
                ],
            ]],
            'finish_reason' => 'tool_calls',
        ]));

        $this->expectException(RuntimeLimitExceededException::class);

        app(AgentRuntime::class)->run(
            'example',
            'conv-1',
            'Loop forever',
            RunContextData::make(['user_id' => 1]),
        );
    }

    public function test_it_pauses_run_when_tool_requires_approval(): void
    {
        config()->set('limen-ai.tools.confirmation_tool', [
            'name' => 'Confirmation Tool',
            'description' => 'Needs approval.',
            'class' => \LimenAi\Tests\Stubs\EchoTool::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
            'confirmation' => true,
        ]);

        config()->set('limen-ai.agents.example.tools', ['confirmation_tool']);

        app(FakeLlmProvider::class)->queueResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_approve',
                'function' => [
                    'name' => 'confirmation_tool',
                    'arguments' => json_encode(['message' => 'send it']),
                ],
            ]],
            'finish_reason' => 'tool_calls',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-approval',
            'Send message',
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::WAITING_APPROVAL, $run['status']);
    }
}
