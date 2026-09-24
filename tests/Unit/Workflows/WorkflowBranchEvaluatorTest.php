<?php

namespace LimenAi\Tests\Unit\Workflows;

use LimenAi\Workflows\WorkflowBranchEvaluator;
use LimenAi\Workflows\WorkflowVariableResolver;
use LimenAi\Tests\TestCase;

class WorkflowBranchEvaluatorTest extends TestCase
{
    public function test_it_routes_to_then_branch_when_condition_matches(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $next = $evaluator->nextStep([
            'condition' => [
                'field' => 'input.mode',
                'operator' => 'equals',
                'value' => 'tool',
            ],
            'then' => 'echo',
            'else' => 'finish',
        ], [
            'input' => ['mode' => 'tool'],
        ]);

        $this->assertSame('echo', $next);
    }

    public function test_it_routes_to_else_branch_when_condition_fails(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $next = $evaluator->nextStep([
            'condition' => [
                'field' => 'input.mode',
                'operator' => 'equals',
                'value' => 'tool',
            ],
            'then' => 'echo',
            'else' => 'finish',
        ], [
            'input' => ['mode' => 'chat'],
        ]);

        $this->assertSame('finish', $next);
    }
}
