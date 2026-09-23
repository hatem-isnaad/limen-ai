<?php

namespace LimenAi\Broadcasting;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;

class PusherBroadcaster implements RealtimeBroadcaster
{
    public function __construct(
        private readonly BroadcastFactory $broadcast,
        private readonly ConfigRepository $config,
    ) {}

    public function broadcast(string $channel, string $event, array $payload): void
    {
        $connection = $this->config->get('limen-ai.broadcasting.connection');
        $broadcaster = $connection
            ? $this->broadcast->connection((string) $connection)
            : $this->broadcast->connection();

        $broadcaster->broadcast([$channel], $event, $payload);
    }

    public function conversationChannel(string $conversationId): string
    {
        $prefix = (string) $this->config->get('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation');

        return $prefix.'.'.$conversationId;
    }
}
