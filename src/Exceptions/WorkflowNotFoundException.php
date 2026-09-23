<?php

namespace LimenAi\Exceptions;

class WorkflowNotFoundException extends ToolException
{
    public static function forKey(string $key): self
    {
        return new self("Workflow [{$key}] was not found.");
    }

    public static function forStep(string $workflowKey, string $stepKey): self
    {
        return new self("Workflow [{$workflowKey}] step [{$stepKey}] was not found.");
    }
}
