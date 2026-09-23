<?php

namespace LimenAi\Runtime;

use LimenAi\Exceptions\RuntimeLimitExceededException;

class RuntimeLimits
{
    /**
     * @param  array<string, mixed>  $limits
     */
    public function __construct(
        private readonly array $limits,
        private readonly float $startedAt,
        private int $currentStep = 0,
        private int $toolCallCount = 0,
    ) {}

    public function nextStep(): void
    {
        $this->currentStep++;
        $this->assertMaxSteps();
        $this->assertTimeout();
    }

    public function recordToolCalls(int $count = 1): void
    {
        $this->toolCallCount += $count;
        $this->assertMaxToolCalls();
    }

    public function currentStep(): int
    {
        return $this->currentStep;
    }

    public function toolCallCount(): int
    {
        return $this->toolCallCount;
    }

    protected function assertMaxSteps(): void
    {
        $maxSteps = (int) ($this->limits['max_steps'] ?? 20);

        if ($this->currentStep > $maxSteps) {
            throw RuntimeLimitExceededException::forLimit('max_steps', $maxSteps);
        }
    }

    protected function assertMaxToolCalls(): void
    {
        $maxToolCalls = (int) ($this->limits['max_tool_calls'] ?? 10);

        if ($this->toolCallCount > $maxToolCalls) {
            throw RuntimeLimitExceededException::forLimit('max_tool_calls', $maxToolCalls);
        }
    }

    protected function assertTimeout(): void
    {
        $timeout = (int) ($this->limits['timeout'] ?? $this->limits['max_execution_time'] ?? 120);
        $elapsed = microtime(true) - $this->startedAt;

        if ($elapsed > $timeout) {
            throw RuntimeLimitExceededException::forLimit('timeout', $timeout);
        }
    }
}
