<?php

namespace LimenAi\Tests\Unit\Workflows;

use LimenAi\Workflows\WorkflowVariableResolver;
use LimenAi\Tests\TestCase;

class WorkflowVariableResolverTest extends TestCase
{
    public function test_it_resolves_nested_state_paths(): void
    {
        $resolver = new WorkflowVariableResolver();

        $value = $resolver->resolve('input.mode', [
            'input' => ['mode' => 'tool'],
            'step_outputs' => [],
        ]);

        $this->assertSame('tool', $value);
    }

    public function test_it_resolves_template_placeholders(): void
    {
        $resolver = new WorkflowVariableResolver();

        $resolved = $resolver->resolveTemplate('Hello {{ input.name }}', [
            'input' => ['name' => 'Limen'],
        ]);

        $this->assertSame('Hello Limen', $resolved);
    }

    public function test_it_resolves_nested_input_arrays_for_tools(): void
    {
        $resolver = new WorkflowVariableResolver();

        $resolved = $resolver->resolveArray([
            'message' => '{{ step_outputs.draft.output }}',
        ], [
            'step_outputs' => [
                'draft' => ['output' => 'Delayed shipment notice'],
            ],
        ]);

        $this->assertSame('Delayed shipment notice', $resolved['message']);
    }
}
