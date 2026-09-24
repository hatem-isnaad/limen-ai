<?php

namespace LimenAi\Ai\Sdk;

use LimenAi\Ai\Contracts\Agent;
use LimenAi\Ai\Promptable;
use Stringable;

/** Prompt handle for registry-backed anonymous (config) agents. */
final class DynamicPromptAgent implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $agentKey,
    ) {}

    public function key(): string
    {
        return $this->agentKey;
    }

    public function instructions(): string|Stringable
    {
        return '';
    }
}
