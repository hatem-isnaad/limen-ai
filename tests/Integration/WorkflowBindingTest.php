<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Tests\TestCase;
use LimenAi\Workflows\DefaultWorkflowEngine;
use LimenAi\Workflows\WorkflowStepRunner;
use LimenAi\Workflows\WorkflowValidator;

class WorkflowBindingTest extends TestCase
{
    public function test_workflow_contracts_are_bound(): void
    {
        $this->assertInstanceOf(DefaultWorkflowEngine::class, app(WorkflowEngine::class));
        $this->assertInstanceOf(WorkflowStepRunner::class, app(WorkflowStepRunner::class));
        $this->assertInstanceOf(WorkflowValidator::class, app(WorkflowValidator::class));
    }
}
