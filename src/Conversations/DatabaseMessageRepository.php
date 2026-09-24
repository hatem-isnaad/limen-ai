<?php

namespace LimenAi\Conversations;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use LimenAi\Contracts\Conversations\MessageRepository;

class DatabaseMessageRepository implements MessageRepository
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function create(string $conversationId, array $attributes): string
    {
        $messageId = (string) ($attributes['id'] ?? Str::uuid());

        $this->db->table('limen_ai_messages')->insert([
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'role' => (string) ($attributes['role'] ?? 'user'),
            'content' => $attributes['content'] ?? null,
            'structured_content' => isset($attributes['structured_content'])
                ? json_encode($attributes['structured_content'], JSON_THROW_ON_ERROR)
                : null,
            'metadata' => isset($attributes['metadata']) && $attributes['metadata'] !== []
                ? json_encode($attributes['metadata'], JSON_THROW_ON_ERROR)
                : null,
            'created_at' => $attributes['created_at'] ?? now(),
        ]);

        return $messageId;
    }

    public function forConversation(string $conversationId, ?int $limit = null): array
    {
        $query = $this->db->table('limen_ai_messages')
            ->where('conversation_id', $conversationId);

        if ($limit !== null) {
            $rows = $query->orderByDesc('created_at')->limit($limit)->get()->reverse()->values()->all();
        } else {
            $rows = $query->orderBy('created_at')->get()->all();
        }

        return array_map(
            fn (object $row): array => $this->mapRow((array) $row),
            $rows,
        );
    }

    /** @param  array<string, mixed>  $row */
    protected function mapRow(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'conversation_id' => (string) $row['conversation_id'],
            'role' => (string) $row['role'],
            'content' => (string) ($row['content'] ?? ''),
            'structured_content' => isset($row['structured_content'])
                ? json_decode((string) $row['structured_content'], true, 512, JSON_THROW_ON_ERROR)
                : null,
            'metadata' => isset($row['metadata'])
                ? json_decode((string) $row['metadata'], true, 512, JSON_THROW_ON_ERROR)
                : [],
            'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : null,
        ];
    }
}
