<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class RunCommandTest extends TestCase
{
    public function test_it_runs_a_single_message_session(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Interactive response.',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:run', [
            'agent' => 'example',
            '--message' => 'Hello',
        ])
            ->expectsOutputToContain('Interactive response.')
            ->assertSuccessful();
    }
}
