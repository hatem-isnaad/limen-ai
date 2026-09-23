<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $conversationId,
        public readonly string $state,
    ) {}
}
