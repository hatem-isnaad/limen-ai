<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $approvalId,
        public readonly string $runId,
        public readonly string $toolKey,
        /** @var array<string, mixed> */
        public readonly array $payload,
    ) {}
}
