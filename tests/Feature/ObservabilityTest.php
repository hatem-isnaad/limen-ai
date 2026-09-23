<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Contracts\Observability\AuditExporter;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Observability\RunObservabilityReporter;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class ObservabilityTest extends TestCase
{
    public function test_agent_run_records_trace_audit_and_llm_usage(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Observed response.',
            'finish_reason' => 'stop',
            'usage' => ['total_tokens' => 128, 'prompt_tokens' => 80, 'completion_tokens' => 48],
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-obs',
            'Observe me',
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);

        $this->assertNotEmpty($run['trace_id'] ?? null);
        $this->assertNotEmpty($run['span_id'] ?? null);

        $audit = app(AuditExporter::class)->export($runId);
        $actions = array_column($audit, 'action');

        $this->assertContains('agent.started', $actions);
        $this->assertContains('agent.completed', $actions);

        $usage = app(UsageReader::class)->recordsForRun($runId);

        $this->assertNotEmpty($usage);
        $this->assertSame('llm', $usage[0]['type']);
        $this->assertSame(128, $usage[0]['usage']['total_tokens']);
    }

    public function test_tool_execution_records_usage_and_trace_spans_in_audit(): void
    {
        $fake = app(FakeLlmProvider::class);
        $fake->queueResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_obs',
                'function' => [
                    'name' => 'example_echo',
                    'arguments' => json_encode(['message' => 'trace me']),
                ],
            ]],
            'finish_reason' => 'tool_calls',
            'usage' => ['total_tokens' => 10],
        ]));
        $fake->queueResponse(LlmResponseData::fromArray([
            'content' => 'Tool traced.',
            'finish_reason' => 'stop',
            'usage' => ['total_tokens' => 20],
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-tool-obs',
            'Echo with trace',
            RunContextData::make(['user_id' => 1]),
        );

        $audit = app(AuditExporter::class)->export($runId);
        $toolStarted = collect($audit)->first(fn (array $entry): bool => ($entry['action'] ?? '') === 'tool.started');

        $this->assertNotNull($toolStarted);
        $this->assertSame($runId, $toolStarted['context']['run_id']);
        $this->assertNotEmpty($toolStarted['context']['trace_id'] ?? null);
        $this->assertNotEmpty($toolStarted['context']['span_id'] ?? null);

        $toolUsage = collect(app(UsageReader::class)->recordsForRun($runId))
            ->first(fn (array $record): bool => ($record['type'] ?? '') === 'tool');

        $this->assertNotNull($toolUsage);
        $this->assertSame('example_echo', $toolUsage['tool_key']);
    }

    public function test_observability_report_endpoint_returns_trace_audit_and_usage(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Report me.',
            'finish_reason' => 'stop',
            'usage' => ['total_tokens' => 5],
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');
        $runId = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Generate report',
        ])->json('run_id');

        $response = $this->getJson("/limen-ai/runs/{$runId}/observability");

        $response->assertOk()
            ->assertJsonPath('observability.run_id', $runId)
            ->assertJsonStructure([
                'observability' => ['trace', 'audit', 'usage'],
            ]);

        $report = app(RunObservabilityReporter::class)->forRun($runId);

        $this->assertNotEmpty($report['trace']['trace_id'] ?? null);
        $this->assertNotEmpty($report['audit']);
        $this->assertNotEmpty($report['usage']);
    }
}
