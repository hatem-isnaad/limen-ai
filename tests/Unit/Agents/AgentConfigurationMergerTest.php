<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\AgentConfigurationMerger;
use LimenAi\Tests\TestCase;

class AgentConfigurationMergerTest extends TestCase
{
    public function test_it_merges_global_defaults_without_overriding_agent_values(): void
    {
        config()->set('limen-ai.agent_defaults', [
            'model' => 'default-model',
            'persona' => [
                'tone' => 'professional',
                'gender' => 'neutral',
                'region' => 'international',
            ],
            'limits' => [
                'max_tokens' => 1000,
            ],
        ]);

        $merged = app(AgentConfigurationMerger::class)->merge([
            'name' => 'Demo',
            'model' => 'custom-model',
            'persona' => [
                'gender' => 'female',
                'region' => 'eg',
            ],
            'limits' => [
                'temperature' => 0.3,
            ],
        ]);

        $this->assertSame('custom-model', $merged['model']);
        $this->assertSame('female', $merged['persona']['gender']);
        $this->assertSame('eg', $merged['persona']['region']);
        $this->assertSame('professional', $merged['persona']['tone']);
        $this->assertSame(0.3, $merged['limits']['temperature']);
        $this->assertSame(1000, $merged['limits']['max_tokens']);
    }
}
