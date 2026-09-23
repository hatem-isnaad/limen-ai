<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Facades\LimenAi;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Support\LimenAiManager;
use LimenAi\Tests\TestCase;

class LimenAiManagerTest extends TestCase
{
    public function test_manager_and_facade_run_agents(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Facade response.',
            'finish_reason' => 'stop',
        ]));

        $runId = app(LimenAiManager::class)->run('example', 'conv-facade', 'Hi', [
            'user_id' => 1,
        ]);

        $this->assertNotSame('', $runId);

        $facadeRunId = LimenAi::run('example', 'conv-facade-2', 'Hi', [
            'user_id' => 1,
        ]);

        $this->assertNotSame('', $facadeRunId);
    }
}
