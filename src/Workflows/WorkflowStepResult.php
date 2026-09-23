<?php

namespace LimenAi\Workflows;

final class WorkflowStepResult
{
    /** @param  array<string, mixed>  $output */
    public function __construct(
        private readonly string $stepKey,
        private readonly array $output,
        private readonly ?string $nextStep,
        private readonly bool $paused = false,
    ) {}

    public function stepKey(): string
    {
        return $this->stepKey;
    }

    /** @return array<string, mixed> */
    public function output(): array
    {
        return $this->output;
    }

    public function nextStep(): ?string
    {
        return $this->nextStep;
    }

    public function paused(): bool
    {
        return $this->paused;
    }
}
