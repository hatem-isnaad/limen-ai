<?php

namespace LimenAi\Workflows;

class WorkflowBranchEvaluator
{
    public function __construct(
        private readonly WorkflowVariableResolver $variables,
    ) {}

    /** @param  array<string, mixed>  $step  @param  array<string, mixed>  $state */
    public function evaluate(array $step, array $state): bool
    {
        $condition = $step['condition'] ?? [];

        if ($condition === []) {
            return false;
        }

        $field = (string) ($condition['field'] ?? '');
        $operator = (string) ($condition['operator'] ?? 'equals');
        $expected = $condition['value'] ?? null;
        $actual = $this->variables->resolve($field, $state);

        return match ($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'contains' => is_string($actual) && is_string($expected) && str_contains($actual, $expected),
            'empty' => $actual === null || $actual === '' || $actual === [],
            default => false,
        };
    }

    /** @param  array<string, mixed>  $step */
    public function nextStep(array $step, array $state): ?string
    {
        $next = $this->evaluate($step, $state)
            ? ($step['then'] ?? null)
            : ($step['else'] ?? null);

        if ($next === null || $next === '') {
            return null;
        }

        return (string) $next;
    }
}
