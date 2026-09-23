<?php

namespace LimenAi\Tests\Unit\Observability;

use Illuminate\Contracts\Events\Dispatcher;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentFailed;
use LimenAi\Events\AgentStarted;
use LimenAi\Observability\AgentObservabilityListener;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use Mockery;

class AgentObservabilityListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_subscribe_registers_event_handlers(): void
    {
        $listener = app(AgentObservabilityListener::class);

        $events = Mockery::mock(Dispatcher::class);
        $events->shouldReceive('listen')
            ->once()
            ->with(AgentStarted::class, [$listener, 'handleAgentStarted']);
        $events->shouldReceive('listen')
            ->once()
            ->with(AgentCompleted::class, [$listener, 'handleAgentCompleted']);
        $events->shouldReceive('listen')
            ->once()
            ->with(AgentFailed::class, [$listener, 'handleAgentFailed']);

        $listener->subscribe($events);

        $this->addToAssertionCount(1);
    }

    public function test_handle_agent_started_logs_audit_event(): void
    {
        $this->expectNotToPerformAssertions();
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('agent.started', Mockery::on(function (array $context): bool {
                return $context['run_id'] === 'run-1'
                    && $context['agent_key'] === 'example'
                    && $context['conversation_id'] === 'conv-1'
                    && $context['user_id'] === 5
                    && $context['trace_id'] === 'trace-abc';
            }));

        $listener = new AgentObservabilityListener($audit);
        $listener->handleAgentStarted(new AgentStarted(
            'run-1',
            'example',
            'conv-1',
            RunContextData::make(['user_id' => 5, 'metadata' => ['trace_id' => 'trace-abc']]),
        ));
    }

    public function test_handle_agent_started_logs_active_skill_keys(): void
    {
        $this->expectNotToPerformAssertions();
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('agent.started', Mockery::on(function (array $context): bool {
                return $context['skill_keys'] === ['general_assistance']
                    && $context['skill_count'] === 1;
            }));

        $listener = new AgentObservabilityListener($audit);
        $listener->handleAgentStarted(new AgentStarted(
            'run-skill',
            'example',
            'conv-skill',
            RunContextData::make(['user_id' => 1]),
            ['general_assistance'],
        ));
    }

    public function test_handle_agent_completed_logs_final_message(): void
    {
        $this->expectNotToPerformAssertions();
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('agent.completed', Mockery::on(function (array $context): bool {
                return $context['final_message'] === 'Done.';
            }));

        $listener = new AgentObservabilityListener($audit);
        $listener->handleAgentCompleted(new AgentCompleted(
            'run-2',
            'example',
            'conv-2',
            'Done.',
            RunContextData::make(['user_id' => 1]),
        ));
    }

    public function test_handle_agent_failed_logs_error(): void
    {
        $this->expectNotToPerformAssertions();
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('agent.failed', Mockery::on(function (array $context): bool {
                return $context['error'] === 'Provider timeout';
            }));

        $listener = new AgentObservabilityListener($audit);
        $listener->handleAgentFailed(new AgentFailed(
            'run-3',
            'example',
            'conv-3',
            'Provider timeout',
            RunContextData::make(),
        ));
    }

    public function test_context_omits_user_id_for_non_run_context_data(): void
    {
        $this->expectNotToPerformAssertions();
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('agent.started', Mockery::on(function (array $context): bool {
                return ! array_key_exists('user_id', $context);
            }));

        $context = Mockery::mock(\LimenAi\Contracts\Runtime\RunContext::class);

        $listener = new AgentObservabilityListener($audit);
        $listener->handleAgentStarted(new AgentStarted('run-4', 'example', 'conv-4', $context));
    }
}
