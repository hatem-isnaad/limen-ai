<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AgentStreamDelta
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $runId,
        public readonly string $conversationId,
        public readonly string $agentKey,
        public readonly string $delta,
        public readonly bool $done,
    ) {}
}
