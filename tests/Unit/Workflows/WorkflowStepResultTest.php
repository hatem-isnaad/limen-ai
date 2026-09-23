<?php

namespace LimenAi\Tests\Unit\Workflows;

use LimenAi\Workflows\WorkflowStepResult;
use PHPUnit\Framework\TestCase;

class WorkflowStepResultTest extends TestCase
{
    public function test_it_exposes_step_output_and_next_step(): void
    {
        $result = new WorkflowStepResult(
            stepKey: 'step_a',
            output: ['message' => 'done'],
            nextStep: 'step_b',
        );

        $this->assertSame('step_a', $result->stepKey());
        $this->assertSame(['message' => 'done'], $result->output());
        $this->assertSame('step_b', $result->nextStep());
        $this->assertFalse($result->paused());
    }

    public function test_it_marks_paused_approval_steps(): void
    {
        $result = new WorkflowStepResult(
            stepKey: 'approval',
            output: ['message' => 'Approval required.'],
            nextStep: 'after_approval',
            paused: true,
        );

        $this->assertTrue($result->paused());
    }

    public function test_next_step_can_be_null(): void
    {
        $result = new WorkflowStepResult('final', [], null);

        $this->assertNull($result->nextStep());
    }
}
