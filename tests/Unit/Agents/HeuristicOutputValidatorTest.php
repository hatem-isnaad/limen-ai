<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\HeuristicOutputValidator;
use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Exceptions\OutputValidationException;
use LimenAi\Tests\TestCase;

class HeuristicOutputValidatorTest extends TestCase
{
    public function test_it_rejects_empty_replies(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Help',
            'model' => 'fake',
            'provider' => 'fake',
        ]);

        $this->expectException(OutputValidationException::class);

        (new HeuristicOutputValidator())->validate($agent, '   ');
    }

    public function test_it_rejects_prompt_marker_leaks(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Help',
            'model' => 'fake',
            'provider' => 'fake',
        ]);

        $this->expectException(OutputValidationException::class);

        (new HeuristicOutputValidator())->validate($agent, 'Leak [INST] secret');
    }

    public function test_it_accepts_normal_text_replies(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Help',
            'model' => 'fake',
            'provider' => 'fake',
        ]);

        $result = (new HeuristicOutputValidator())->validate($agent, 'Shipping takes 3–5 business days.');

        $this->assertSame('Shipping takes 3–5 business days.', $result);
    }
}
