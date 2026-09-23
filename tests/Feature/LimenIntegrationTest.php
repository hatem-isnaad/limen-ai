<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LimenAi\Authorization\ApprovalStatus;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Events\ApprovalRequested;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\Stubs\Limen\FakeShipmentService;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ToolPipeline;

class LimenIntegrationTest extends TestCase
{
    public function test_shipment_lookup_demo_calls_host_tool_and_returns_status(): void
    {
        app(FakeLlmProvider::class)->queueResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_shipment',
                'function' => [
                    'name' => 'get_shipment_status',
                    'arguments' => json_encode(['shipment_id' => '12345']),
                ],
            ]],
            'finish_reason' => 'tool_calls',
        ]))->queueResponse(LlmResponseData::fromArray([
            'content' => 'Shipment 12345 is in transit to Riyadh.',
            'finish_reason' => 'stop',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'limen_3pl',
            'conv-limen-lookup',
            'Where is shipment 12345?',
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertStringContainsString('in transit', (string) ($run['final_message'] ?? ''));

        $toolCalls = app(FakeLlmProvider::class)->recordedCalls();
        $this->assertCount(2, $toolCalls);
    }

    public function test_get_shipment_status_tool_reads_from_host_shipment_service(): void
    {
        $result = app(ToolPipeline::class)->execute(
            'get_shipment_status',
            ['shipment_id' => '67890'],
            RunContextData::make(['user_id' => 1])->forToolExecution('run-tool', 'conv-tool', 'limen_3pl'),
        );

        $this->assertTrue($result->output()['found']);
        $this->assertSame('delayed', $result->output()['shipment']['status']);
        $this->assertSame('Customs inspection', $result->output()['shipment']['delay_reason']);
    }

    public function test_send_customer_message_requires_approval_before_execution(): void
    {
        Event::fake([ApprovalRequested::class]);

        app(FakeLlmProvider::class)->queueResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_notify',
                'function' => [
                    'name' => 'send_customer_message',
                    'arguments' => json_encode([
                        'shipment_id' => '67890',
                        'message' => 'Your shipment is delayed due to customs inspection.',
                    ]),
                ],
            ]],
            'finish_reason' => 'tool_calls',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'limen_3pl',
            'conv-limen-approval',
            'Notify the customer about the delay on shipment 67890.',
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);
        $approval = app(ApprovalRepository::class)->findPendingForRun($runId);

        $this->assertSame(RunStatus::WAITING_APPROVAL, $run['status']);
        $this->assertNotNull($approval);
        $this->assertSame(ApprovalStatus::PENDING, $approval['status']);
        Event::assertDispatched(ApprovalRequested::class);
    }

    public function test_delayed_shipment_workflow_resumes_and_sends_after_approval(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Your shipment is delayed due to customs inspection.',
            'finish_reason' => 'stop',
        ]));

        $workflow = app(WorkflowRepository::class)->find('shipment_notify');
        $engine = app(WorkflowEngine::class);
        $context = RunContextData::make(['user_id' => 1]);

        $runId = $engine->start($workflow, ['shipment_id' => '67890'], $context);

        $this->assertSame(RunStatus::WAITING_APPROVAL, app(RunRepository::class)->find($runId)['status']);

        $engine->resume($runId, $context);

        $completed = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $completed['status']);
        $this->assertTrue($completed['step_outputs']['send']['output']['sent']);
        $this->assertSame('67890', $completed['step_outputs']['send']['output']['shipment_id']);
    }

    public function test_limen_tools_are_registered_in_tool_repository(): void
    {
        $tools = app(ToolRepository::class);

        $this->assertNotNull($tools->find('get_shipment_status'));
        $this->assertNotNull($tools->find('send_customer_message'));
        $this->assertTrue($tools->find('send_customer_message')->requiresConfirmation());
    }

    public function test_fake_shipment_service_is_isolated_from_package_src(): void
    {
        $this->assertInstanceOf(FakeShipmentService::class, app(FakeShipmentService::class));
        $this->assertFileDoesNotExist(dirname(__DIR__, 2).'/src/Models/Shipment.php');
    }
}
