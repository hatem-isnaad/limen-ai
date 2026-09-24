<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Facades\LimenAi;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class LimenAiManagerTest extends TestCase
{
    public function test_facade_can_register_faq_and_run_default_agent(): void
    {
        LimenAi::faq('getting_started', 'What is Limen AI?', 'A Laravel-native agent framework.');

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Limen AI is ready.',
            'finish_reason' => 'stop',
            'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 6, 'total_tokens' => 18],
        ]));

        $runId = LimenAi::run(
            'example',
            'conv-manager',
            'Hello',
            RunContextData::make(['user_id' => 1]),
        );

        $summary = LimenAi::usageSummary($runId);

        $this->assertNotSame('', $runId);
        $this->assertSame(1, $summary['llm_calls']);
        $this->assertSame(12, $summary['input_tokens']);
        $this->assertSame(6, $summary['output_tokens']);
    }
}
