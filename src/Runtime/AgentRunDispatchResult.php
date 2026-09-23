<?php

namespace LimenAi\Runtime;

final class AgentRunDispatchResult
{
    public function __construct(
        public readonly bool $queued,
        public readonly ?string $runId = null,
    ) {}
}
