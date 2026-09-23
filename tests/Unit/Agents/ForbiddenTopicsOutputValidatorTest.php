<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Agents\ForbiddenTopicsOutputValidator;
use LimenAi\Exceptions\OutputValidationException;
use LimenAi\Tests\TestCase;

class ForbiddenTopicsOutputValidatorTest extends TestCase
{
    public function test_it_blocks_forbidden_topic_matches(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Help',
            'model' => 'fake',
            'provider' => 'fake',
            'persona' => [
                'forbidden_topics' => ['password'],
            ],
        ]);

        $this->expectException(OutputValidationException::class);

        (new ForbiddenTopicsOutputValidator())->validate($agent, 'Your password is abc123');
    }
}
