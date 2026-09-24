<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Workflows\DefaultWorkflowEngine;
use LimenAi\Workflows\WorkflowStepRunner;
use LimenAi\Workflows\WorkflowValidator;
use LimenAi\Tests\TestCase;

class WorkflowBindingTest extends TestCase
{
    public function test_workflow_contracts_are_bound(): void
    {
        $this->assertInstanceOf(DefaultWorkflowEngine::class, app(WorkflowEngine::class));
        $this->assertInstanceOf(WorkflowStepRunner::class, app(WorkflowStepRunner::class));
        $this->assertInstanceOf(WorkflowValidator::class, app(WorkflowValidator::class));
    }
}
