<?php

namespace LimenAi\Tests\Unit\Workflows;

use LimenAi\Workflows\WorkflowValidator;
use LimenAi\Tests\TestCase;

class WorkflowValidatorTest extends TestCase
{
    public function test_it_validates_example_workflow_configuration(): void
    {
        $errors = app(WorkflowValidator::class)->validate('example_flow');

        $this->assertSame([], $errors);
    }

    public function test_it_reports_unknown_step_references(): void
    {
        config()->set('limen-ai.workflows.invalid_flow', [
            'name' => 'Invalid',
            'start' => 'start',
            'steps' => [
                'start' => [
                    'type' => 'agent',
                    'agent' => 'example',
                    'next' => 'missing_step',
                ],
            ],
        ]);

        $errors = app(WorkflowValidator::class)->validate('invalid_flow');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('missing_step', implode("\n", $errors));
    }
}
