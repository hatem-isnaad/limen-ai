<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class WorkflowTestCommandTest extends TestCase
{
    public function test_it_dry_runs_a_workflow_from_cli(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Workflow complete.',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:workflow:test', [
            'workflow' => 'example_flow',
            '--input' => '{"mode":"chat"}',
        ])
            ->expectsOutputToContain('example_flow')
            ->assertSuccessful();
    }
}
