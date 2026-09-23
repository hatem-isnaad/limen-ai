<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Agents\StructuredOutputValidator;
use LimenAi\Exceptions\OutputValidationException;
use LimenAi\Tests\TestCase;

class StructuredOutputValidatorTest extends TestCase
{
    public function test_it_accepts_valid_json_for_json_agents(): void
    {
        $agent = $this->agent(['format' => 'json']);

        $result = (new StructuredOutputValidator())->validate($agent, '{"ok":true}');

        $this->assertSame('{"ok":true}', $result);
    }

    public function test_it_skips_validation_for_text_agents(): void
    {
        $agent = $this->agent(['format' => 'text']);

        $result = (new StructuredOutputValidator())->validate($agent, 'not json');

        $this->assertSame('not json', $result);
    }

    public function test_it_rejects_invalid_json_for_json_agents(): void
    {
        $agent = $this->agent(['format' => 'json']);

        $this->expectException(OutputValidationException::class);

        (new StructuredOutputValidator())->validate($agent, '{invalid');
    }

    /** @param  array<string, mixed>  $output */
    protected function agent(array $output): ConfigAgentDefinition
    {
        return ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
            'output' => $output,
        ]);
    }
}
