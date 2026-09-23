<?php

namespace LimenAi\Conversations;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use LimenAi\Contracts\Conversations\ConversationRepository;

class DatabaseConversationRepository implements ConversationRepository
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function create(array $attributes): string
    {
        $conversationId = (string) ($attributes['id'] ?? Str::uuid());
        $now = now();

        $this->db->table('limen_ai_conversations')->insert([
            'id' => $conversationId,
            'agent_key' => (string) ($attributes['agent_key'] ?? ''),
            'user_id' => $attributes['user_id'] ?? null,
            'guest_token' => $attributes['guest_token'] ?? null,
            'title' => $attributes['title'] ?? null,
            'metadata' => isset($attributes['metadata'])
                ? json_encode($attributes['metadata'], JSON_THROW_ON_ERROR)
                : null,
            'state' => (string) ($attributes['state'] ?? ConversationState::ACTIVE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $conversationId;
    }

    public function find(string $conversationId): ?array
    {
        $row = $this->db->table('limen_ai_conversations')
            ->where('id', $conversationId)
            ->first();

        return $row === null ? null : $this->mapRow((array) $row);
    }

    public function update(string $conversationId, array $attributes): void
    {
        if ($this->find($conversationId) === null) {
            return;
        }

        $updates = ['updated_at' => now()];

        foreach (['agent_key', 'user_id', 'guest_token', 'title', 'state'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $updates[$field] = $attributes[$field];
            }
        }

        if (array_key_exists('metadata', $attributes)) {
            $updates['metadata'] = json_encode($attributes['metadata'], JSON_THROW_ON_ERROR);
        }

        $this->db->table('limen_ai_conversations')
            ->where('id', $conversationId)
            ->update($updates);
    }

    /** @param  array<string, mixed>  $row */
    protected function mapRow(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'agent_key' => (string) $row['agent_key'],
            'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'guest_token' => $row['guest_token'],
            'title' => $row['title'],
            'metadata' => isset($row['metadata'])
                ? json_decode((string) $row['metadata'], true, 512, JSON_THROW_ON_ERROR)
                : [],
            'state' => (string) $row['state'],
            'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : null,
            'updated_at' => isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        ];
    }
}
