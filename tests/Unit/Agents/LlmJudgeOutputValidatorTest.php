<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Agents\LlmJudgeOutputValidator;
use LimenAi\Exceptions\OutputValidationException;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class LlmJudgeOutputValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.quality.semantic_validation', true);
        config()->set('limen-ai.quality.semantic_min_score', 0.6);
    }

    public function test_it_passes_when_judge_returns_pass(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => '{"pass":true,"score":0.9,"reason":"helpful"}',
            'finish_reason' => 'stop',
        ]));

        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Help',
            'model' => 'fake',
            'provider' => 'fake',
        ]);

        $result = app(LlmJudgeOutputValidator::class)->validate($agent, 'Returns are accepted within 14 days.');

        $this->assertSame('Returns are accepted within 14 days.', $result);
    }

    public function test_it_rejects_when_judge_returns_fail(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => '{"pass":false,"score":0.2,"reason":"off-topic"}',
            'finish_reason' => 'stop',
        ]));

        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Help',
            'model' => 'fake',
            'provider' => 'fake',
        ]);

        $this->expectException(OutputValidationException::class);

        app(LlmJudgeOutputValidator::class)->validate($agent, 'Random unrelated content.');
    }

    public function test_it_is_lenient_when_judge_output_is_unparseable(): void
    {
        config()->set('limen-ai.quality.semantic_validation_strict', false);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'not-json',
            'finish_reason' => 'stop',
        ]));

        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Help',
            'model' => 'fake',
            'provider' => 'fake',
        ]);

        $result = app(LlmJudgeOutputValidator::class)->validate($agent, 'Still helpful.');

        $this->assertSame('Still helpful.', $result);
    }
}
