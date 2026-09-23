<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $messageId,
        public readonly string $conversationId,
        public readonly string $role,
    ) {}
}
