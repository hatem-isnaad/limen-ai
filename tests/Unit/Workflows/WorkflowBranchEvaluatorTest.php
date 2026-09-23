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

    public function test_evaluate_returns_false_for_empty_condition(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $this->assertFalse($evaluator->evaluate(['condition' => []], ['flag' => true]));
    }

    public function test_it_supports_not_equals_operator(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $next = $evaluator->nextStep([
            'condition' => ['field' => 'status', 'operator' => 'not_equals', 'value' => 'done'],
            'then' => 'continue',
            'else' => 'finish',
        ], ['status' => 'running']);

        $this->assertSame('continue', $next);
    }

    public function test_it_supports_contains_operator(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $next = $evaluator->nextStep([
            'condition' => ['field' => 'message', 'operator' => 'contains', 'value' => 'urgent'],
            'then' => 'escalate',
            'else' => 'normal',
        ], ['message' => 'This is urgent help']);

        $this->assertSame('escalate', $next);
    }

    public function test_it_supports_empty_operator(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $next = $evaluator->nextStep([
            'condition' => ['field' => 'notes', 'operator' => 'empty'],
            'then' => 'collect_notes',
            'else' => 'skip',
        ], ['notes' => '']);

        $this->assertSame('collect_notes', $next);
    }

    public function test_next_step_returns_null_when_branch_target_missing(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $this->assertNull($evaluator->nextStep([
            'condition' => ['field' => 'flag', 'operator' => 'equals', 'value' => true],
        ], ['flag' => false]));
    }

    public function test_it_returns_false_for_unknown_operator(): void
    {
        $evaluator = new WorkflowBranchEvaluator(new WorkflowVariableResolver());

        $this->assertFalse($evaluator->evaluate([
            'condition' => ['field' => 'flag', 'operator' => 'unknown', 'value' => true],
        ], ['flag' => true]));
    }
}
