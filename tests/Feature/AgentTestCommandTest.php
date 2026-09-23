<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class AgentTestCommandTest extends TestCase
{
    public function test_it_runs_an_agent_once_from_cli(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'CLI agent response.',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:agent:test', [
            'agent' => 'example',
            '--message' => 'Ping',
        ])
            ->expectsOutputToContain('CLI agent response.')
            ->assertSuccessful();
    }
}
