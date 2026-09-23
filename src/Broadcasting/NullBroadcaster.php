<?php

namespace LimenAi\Broadcasting;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;

class NullBroadcaster implements RealtimeBroadcaster
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function broadcast(string $channel, string $event, array $payload): void
    {
        // Intentionally no-op for disabled broadcasting or tests.
    }

    public function conversationChannel(string $conversationId): string
    {
        $prefix = (string) $this->config->get('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation');

        return $prefix.'.'.$conversationId;
    }
}
