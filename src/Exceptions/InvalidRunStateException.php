<?php

namespace LimenAi\Exceptions;

class InvalidRunStateException extends ToolException
{
    public static function notWaitingForApproval(string $runId): self
    {
        return new self("Agent run [{$runId}] is not waiting for approval.");
    }

    public static function checkpointMissing(string $runId): self
    {
        return new self("Agent run [{$runId}] has no saved checkpoint.");
    }

    public static function approvalNotPending(string $approvalId): self
    {
        return new self("Approval [{$approvalId}] is not pending.");
    }

    public static function notWorkflowRun(string $runId): self
    {
        return new self("Run [{$runId}] is not a workflow run.");
    }

    public static function invalidWorkflowStart(string $workflowKey): self
    {
        return new self("Workflow [{$workflowKey}] is missing a valid start step.");
    }
}
