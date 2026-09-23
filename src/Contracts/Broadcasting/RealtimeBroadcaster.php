<?php

namespace LimenAi\Contracts\Broadcasting;

interface RealtimeBroadcaster
{
    public function broadcast(string $channel, string $event, array $payload): void;

    public function conversationChannel(string $conversationId): string;
}
