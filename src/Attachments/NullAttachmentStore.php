<?php

namespace LimenAi\Attachments;

use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Exceptions\AttachmentValidationException;

class NullAttachmentStore implements AttachmentStore
{
    public function store(string $conversationId, array $attributes, string $contents): string
    {
        throw AttachmentValidationException::disabled();
    }

    public function find(string $attachmentId): ?array
    {
        return null;
    }

    public function delete(string $attachmentId): void {}

    public function listForConversation(string $conversationId): array
    {
        return [];
    }

    public function countForConversation(string $conversationId): int
    {
        return 0;
    }

    public function readContents(string $attachmentId): ?string
    {
        return null;
    }

    public function markProcessed(string $attachmentId, ?string $extractedText, array $metadata = []): void {}
}
