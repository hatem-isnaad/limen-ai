<?php

namespace LimenAi\Ai\Messages;

final class Message
{
    /**
     * @param  list<array<string, mixed>>  $attachments
     */
    public function __construct(
        public readonly string $role,
        public readonly string $content,
        public readonly array $attachments = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->attachments === []) {
            return [
                'role' => $this->role,
                'content' => $this->content,
            ];
        }

        $parts = [['type' => 'text', 'text' => $this->content]];

        foreach ($this->attachments as $attachment) {
            $parts[] = $attachment;
        }

        return [
            'role' => $this->role,
            'content' => $parts,
        ];
    }
}
