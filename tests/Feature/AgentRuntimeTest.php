<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LimenAi\Conversations\ConversationService;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentStarted;
use LimenAi\Events\MessageCreated;
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

    public function test_it_loads_prior_conversation_history_on_subsequent_runs(): void
    {
        Event::fake([MessageCreated::class]);

        $fake = app(FakeLlmProvider::class);
        $fake->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'First reply.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-multi',
            'Hello',
            RunContextData::make(['user_id' => 1]),
        );

        $fake->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Second reply.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-multi',
            'Follow up',
            RunContextData::make(['user_id' => 1]),
        );

        $calls = $fake->recordedCalls();
        $secondCallMessages = $calls[1]['messages'];

        $userMessages = array_values(array_filter(
            $secondCallMessages,
            fn (array $message): bool => ($message['role'] ?? '') === 'user',
        ));
        $assistantMessages = array_values(array_filter(
            $secondCallMessages,
            fn (array $message): bool => ($message['role'] ?? '') === 'assistant',
        ));

        $this->assertSame('Hello', $userMessages[0]['content']);
        $this->assertSame('First reply.', $assistantMessages[0]['content']);
        $this->assertSame('Follow up', $userMessages[1]['content']);

        $stored = app(ConversationService::class)->storedMessages('conv-multi');
        $this->assertCount(4, $stored);
    }

    public function test_it_persists_only_final_assistant_message_after_tool_call(): void
    {
        $fake = app(FakeLlmProvider::class);
        $fake->queueResponse(LlmResponseData::fromArray([
            'tool_calls' => [[
                'id' => 'call_1',
                'function' => [
                    'name' => 'example_echo',
                    'arguments' => json_encode(['message' => 'myname is hatem elsheref']),
                ],
            ]],
            'finish_reason' => 'tool_calls',
        ]));
        $fake->queueResponse(LlmResponseData::fromArray([
            'content' => 'Echo complete.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-tool-persist',
            'yes echo myname is hatem elsheref',
            RunContextData::make(['user_id' => 1]),
        );

        $stored = app(ConversationService::class)->storedMessages('conv-tool-persist');

        $this->assertCount(2, $stored);
        $this->assertSame('user', $stored[0]['role']);
        $this->assertSame('yes echo myname is hatem elsheref', $stored[0]['content']);
        $this->assertSame('assistant', $stored[1]['role']);
        $this->assertSame('Echo complete.', $stored[1]['content']);
    }

    public function test_it_persists_messages_to_conversation_on_completion(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Persisted response.',
            'finish_reason' => 'stop',
        ]));

        app(AgentRuntime::class)->run(
            'example',
            'conv-persist',
            'Save this',
            RunContextData::make(['user_id' => 1]),
        );

        $stored = app(ConversationService::class)->storedMessages('conv-persist');

        $this->assertCount(2, $stored);
        $this->assertSame('user', $stored[0]['role']);
        $this->assertSame('Save this', $stored[0]['content']);
        $this->assertSame('assistant', $stored[1]['role']);
        $this->assertSame('Persisted response.', $stored[1]['content']);
    }
}
