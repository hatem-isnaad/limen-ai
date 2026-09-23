<?php

namespace LimenAi\Contracts\Attachments;

interface AttachmentStore
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(string $conversationId, array $attributes, string $contents): string;

    /** @return array<string, mixed>|null */
    public function find(string $attachmentId): ?array;

    public function delete(string $attachmentId): void;

    /** @return list<array<string, mixed>> */
    public function listForConversation(string $conversationId): array;

    public function countForConversation(string $conversationId): int;

    public function readContents(string $attachmentId): ?string;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markProcessed(string $attachmentId, ?string $extractedText, array $metadata = []): void;
}
