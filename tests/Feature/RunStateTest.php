<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LimenAi\Authorization\ApprovalStatus;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Events\ApprovalGranted;
use LimenAi\Events\ApprovalRejected;
use LimenAi\Events\ApprovalRequested;
use LimenAi\Exceptions\InvalidRunStateException;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\Stubs\EchoTool;
use LimenAi\Tests\TestCase;

class RunStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.tools.confirmation_tool', [
            'name' => 'Confirmation Tool',
            'description' => 'Needs approval.',
            'class' => EchoTool::class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
            'confirmation' => true,
        ]);

        config()->set('limen-ai.agents.example.tools', ['confirmation_tool']);
    }

    public function test_it_requests_approval_and_persists_checkpoint(): void
    {
        Event::fake([ApprovalRequested::class]);

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
            'conv-state',
            'Send message',
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);
        $checkpoint = app(CheckpointStore::class)->load($runId);
        $approval = app(ApprovalRepository::class)->findPendingForRun($runId);

        $this->assertSame(RunStatus::WAITING_APPROVAL, $run['status']);
        $this->assertNotNull($checkpoint);
        $this->assertNotNull($approval);
        $this->assertSame(ApprovalStatus::PENDING, $approval['status']);

        Event::assertDispatched(ApprovalRequested::class);
    }

    public function test_it_resumes_waiting_run_after_approval(): void
    {
        Event::fake([ApprovalGranted::class, ApprovalRequested::class]);

        $fake = app(FakeLlmProvider::class);
        $fake->queueResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_approve',
                'function' => [
                    'name' => 'confirmation_tool',
                    'arguments' => json_encode(['message' => 'send it']),
                ],
            ]],
            'finish_reason' => 'tool_calls',
        ]));
        $fake->queueResponse(LlmResponseData::fromArray([
            'content' => 'Message sent successfully.',
            'finish_reason' => 'stop',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-resume',
            'Send message',
            RunContextData::make(['user_id' => 1]),
        );

        app(AgentRuntime::class)->resume($runId, RunContextData::make(['user_id' => 1]));

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertSame('Message sent successfully.', $run['final_message']);
        $this->assertNull(app(CheckpointStore::class)->load($runId));
        Event::assertDispatched(ApprovalGranted::class);
    }

    public function test_it_rejects_waiting_run_and_clears_checkpoint(): void
    {
        Event::fake([ApprovalRejected::class, ApprovalRequested::class]);

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
            'conv-reject',
            'Send message',
            RunContextData::make(['user_id' => 1]),
        );

        app(AgentRuntime::class)->reject($runId, RunContextData::make(['user_id' => 1]));

        $run = app(RunRepository::class)->find($runId);
        $approval = app(ApprovalRepository::class)->findPendingForRun($runId);

        $this->assertSame(RunStatus::CANCELLED, $run['status']);
        $this->assertNull($approval);
        $this->assertNull(app(CheckpointStore::class)->load($runId));
        Event::assertDispatched(ApprovalRejected::class);
    }

    public function test_it_cannot_resume_run_that_is_not_waiting_for_approval(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Done.',
            'finish_reason' => 'stop',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-complete',
            'Hello',
            RunContextData::make(['user_id' => 1]),
        );

        $this->expectException(InvalidRunStateException::class);

        app(AgentRuntime::class)->resume($runId, RunContextData::make(['user_id' => 1]));
    }
}
