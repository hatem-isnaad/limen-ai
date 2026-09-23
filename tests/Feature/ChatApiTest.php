<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Auth\GenericUser;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\Stubs\EchoTool;
use LimenAi\Tests\TestCase;

class ChatApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.ui.enabled', true);
        config()->set('limen-ai.ui.middleware', []);
    }

    public function test_it_creates_and_returns_a_conversation(): void
    {
        $response = $this->postJson('/limen-ai/conversations', [
            'agent' => 'example',
        ]);

        $response->assertCreated()
            ->assertJsonPath('conversation.agent_key', 'example')
            ->assertJsonStructure(['conversation' => ['id', 'channel']]);
    }

    public function test_it_sends_a_message_and_completes_a_run(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Hello from API.',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');

        $response = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Hi there',
        ]);

        $response->assertOk()
            ->assertJsonPath('queued', false)
            ->assertJsonStructure(['run_id']);

        $run = app(RunRepository::class)->find($response->json('run_id'));

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertSame('Hello from API.', $run['final_message']);
    }

    public function test_it_returns_run_status_for_polling(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Done.',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');
        $runId = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Status check',
        ])->json('run_id');

        $this->getJson("/limen-ai/runs/{$runId}")
            ->assertOk()
            ->assertJsonPath('run.status', RunStatus::COMPLETED)
            ->assertJsonPath('run.terminal', true);
    }

    public function test_it_denies_access_to_other_users_conversations(): void
    {
        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');

        $this->actingAs(new GenericUser(['id' => 99]));

        $this->getJson("/limen-ai/conversations/{$conversationId}")
            ->assertForbidden();
    }

    public function test_it_resumes_after_approval_via_http(): void
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
        app(FakeLlmProvider::class)->queueResponse(LlmResponseData::fromArray([
            'content' => 'Approved and sent.',
            'finish_reason' => 'stop',
        ]));

        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');
        $runId = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'Send it',
        ])->json('run_id');

        $approval = app(ApprovalRepository::class)->findPendingForRun($runId);
        $this->assertNotNull($approval);

        $this->postJson("/limen-ai/approvals/{$approval['id']}/approve")
            ->assertOk()
            ->assertJsonPath('action', 'approved');

        $run = app(RunRepository::class)->find($runId);
        $this->assertSame(RunStatus::COMPLETED, $run['status']);
    }
}
