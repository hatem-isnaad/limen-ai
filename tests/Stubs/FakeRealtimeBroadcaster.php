<?php

namespace LimenAi\Tests\Stubs;

use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;

class FakeRealtimeBroadcaster implements RealtimeBroadcaster
{
    /** @var list<array{channel: string, event: string, payload: array<string, mixed>}> */
    private array $broadcasts = [];

    public function broadcast(string $channel, string $event, array $payload): void
    {
        $this->broadcasts[] = [
            'channel' => $channel,
            'event' => $event,
            'payload' => $payload,
        ];
    }

    public function conversationChannel(string $conversationId): string
    {
        return 'limen-ai.conversation.'.$conversationId;
    }

    /** @return list<array{channel: string, event: string, payload: array<string, mixed>}> */
    public function broadcasts(): array
    {
        return $this->broadcasts;
    }

    public function clear(): void
    {
        $this->broadcasts = [];
    }
}
