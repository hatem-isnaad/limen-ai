<?php

namespace LimenAi\Exceptions;

class WorkflowDisabledException extends ToolException
{
    public static function forWorkflow(string $workflowKey): self
    {
        return new self("Workflow [{$workflowKey}] is disabled.");
    }
}
