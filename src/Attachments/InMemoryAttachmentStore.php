<?php

namespace LimenAi\Attachments;

use Illuminate\Support\Str;
use LimenAi\Contracts\Attachments\AttachmentStore;

class InMemoryAttachmentStore implements AttachmentStore
{
    /** @var array<string, array<string, mixed>> */
    private array $attachments = [];

    /** @var array<string, string> */
    private array $contents = [];

    public function store(string $conversationId, array $attributes, string $contents): string
    {
        $id = (string) ($attributes['id'] ?? Str::uuid());

        $this->attachments[$id] = array_merge([
            'id' => $id,
            'conversation_id' => $conversationId,
            'status' => AttachmentStatus::PENDING,
            'extracted_text' => null,
            'metadata' => [],
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], $attributes);

        $this->contents[$id] = $contents;

        return $id;
    }

    public function find(string $attachmentId): ?array
    {
        return $this->attachments[$attachmentId] ?? null;
    }

    public function delete(string $attachmentId): void
    {
        unset($this->attachments[$attachmentId], $this->contents[$attachmentId]);
    }

    public function listForConversation(string $conversationId): array
    {
        return array_values(array_filter(
            $this->attachments,
            fn (array $attachment): bool => ($attachment['conversation_id'] ?? null) === $conversationId,
        ));
    }

    public function countForConversation(string $conversationId): int
    {
        return count($this->listForConversation($conversationId));
    }

    public function readContents(string $attachmentId): ?string
    {
        return $this->contents[$attachmentId] ?? null;
    }

    public function markProcessed(string $attachmentId, ?string $extractedText, array $metadata = []): void
    {
        if (! isset($this->attachments[$attachmentId])) {
            return;
        }

        $this->attachments[$attachmentId]['status'] = $extractedText === null || $extractedText === ''
            ? AttachmentStatus::FAILED
            : AttachmentStatus::PROCESSED;
        $this->attachments[$attachmentId]['extracted_text'] = $extractedText;
        $this->attachments[$attachmentId]['metadata'] = array_merge(
            (array) ($this->attachments[$attachmentId]['metadata'] ?? []),
            $metadata,
        );
        $this->attachments[$attachmentId]['updated_at'] = now()->toIso8601String();
    }
}
