<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ToolPipeline;

class DelegateToAgentToolTest extends TestCase
{
    public function test_it_rejects_delegates_not_in_router_allowlist(): void
    {
        config()->set('limen-ai.router.delegates', ['example']);

        $context = RunContextData::make(['user_id' => 1])
            ->forToolExecution('run-delegate', 'conv-delegate', 'router_agent');

        $result = app(ToolPipeline::class)->execute('delegate_to_agent', [
            'agent_key' => 'limen_3pl',
            'message' => 'Status of shipment 12345?',
        ], $context);

        $this->assertFalse($result->output()['delegated']);
        $this->assertSame('delegate_not_allowed', $result->output()['reason']);
    }

    public function test_it_delegates_to_an_allowed_specialist_agent(): void
    {
        config()->set('limen-ai.router.delegates', ['example']);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Delegated specialist reply.',
            'finish_reason' => 'stop',
        ]));

        $context = RunContextData::make(['user_id' => 1])
            ->forToolExecution('run-delegate', 'conv-delegate', 'router_agent');

        $result = app(ToolPipeline::class)->execute('delegate_to_agent', [
            'agent_key' => 'example',
            'message' => 'Echo hello',
        ], $context);

        $this->assertTrue($result->output()['delegated']);
        $this->assertSame('Delegated specialist reply.', $result->output()['final_message']);
    }
}
