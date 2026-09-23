<?php

namespace LimenAi\Attachments;

use Illuminate\Database\Connection;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LimenAi\Contracts\Attachments\AttachmentStore;

class DatabaseAttachmentStore implements AttachmentStore
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $disk = 'local',
        private readonly string $pathPrefix = 'limen-ai/attachments',
    ) {}

    public function store(string $conversationId, array $attributes, string $contents): string
    {
        $id = (string) ($attributes['id'] ?? Str::uuid());
        $originalName = (string) ($attributes['original_name'] ?? 'attachment.bin');
        $mimeType = (string) ($attributes['mime_type'] ?? 'application/octet-stream');
        $relativePath = trim($this->pathPrefix, '/').'/'.$conversationId.'/'.$id.'/'.basename($originalName);

        $this->disk()->put($relativePath, $contents);

        $this->connection->table('limen_ai_attachments')->insert([
            'id' => $id,
            'conversation_id' => $conversationId,
            'user_id' => $attributes['user_id'] ?? null,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($contents),
            'disk' => $this->disk,
            'path' => $relativePath,
            'extracted_text' => null,
            'status' => AttachmentStatus::PENDING,
            'metadata' => json_encode($attributes['metadata'] ?? [], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function find(string $attachmentId): ?array
    {
        $row = $this->connection->table('limen_ai_attachments')->where('id', $attachmentId)->first();

        return $row === null ? null : $this->mapRow((array) $row);
    }

    public function delete(string $attachmentId): void
    {
        $attachment = $this->find($attachmentId);

        if ($attachment === null) {
            return;
        }

        if ($this->disk()->exists((string) $attachment['path'])) {
            $this->disk()->delete((string) $attachment['path']);
        }

        $this->connection->table('limen_ai_attachments')->where('id', $attachmentId)->delete();
    }

    public function listForConversation(string $conversationId): array
    {
        return array_map(
            fn (object $row): array => $this->mapRow((array) $row),
            $this->connection->table('limen_ai_attachments')
                ->where('conversation_id', $conversationId)
                ->orderBy('created_at')
                ->get()
                ->all(),
        );
    }

    public function countForConversation(string $conversationId): int
    {
        return (int) $this->connection->table('limen_ai_attachments')
            ->where('conversation_id', $conversationId)
            ->count();
    }

    public function readContents(string $attachmentId): ?string
    {
        $attachment = $this->find($attachmentId);

        if ($attachment === null) {
            return null;
        }

        $path = (string) $attachment['path'];

        return $this->disk()->exists($path) ? $this->disk()->get($path) : null;
    }

    public function markProcessed(string $attachmentId, ?string $extractedText, array $metadata = []): void
    {
        $attachment = $this->find($attachmentId);

        if ($attachment === null) {
            return;
        }

        $mergedMetadata = array_merge((array) ($attachment['metadata'] ?? []), $metadata);

        $this->connection->table('limen_ai_attachments')
            ->where('id', $attachmentId)
            ->update([
                'extracted_text' => $extractedText,
                'status' => $extractedText === null || $extractedText === ''
                    ? AttachmentStatus::FAILED
                    : AttachmentStatus::PROCESSED,
                'metadata' => json_encode($mergedMetadata, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }

    /** @param  array<string, mixed>  $row */
    protected function mapRow(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'conversation_id' => (string) $row['conversation_id'],
            'user_id' => $row['user_id'] !== null ? (int) $row['user_id'] : null,
            'original_name' => (string) $row['original_name'],
            'mime_type' => (string) $row['mime_type'],
            'size_bytes' => (int) $row['size_bytes'],
            'disk' => (string) $row['disk'],
            'path' => (string) $row['path'],
            'extracted_text' => $row['extracted_text'],
            'status' => (string) $row['status'],
            'metadata' => json_decode((string) ($row['metadata'] ?? '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    protected function disk(): FilesystemAdapter
    {
        return Storage::disk($this->disk);
    }
}
