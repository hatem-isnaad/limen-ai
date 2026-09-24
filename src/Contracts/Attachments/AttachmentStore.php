<?php

namespace LimenAi\Contracts\Attachments;

interface AttachmentStore
{
    public function store(string $conversationId, array $fileMeta): string;

    public function find(string $attachmentId): ?array;

    public function delete(string $attachmentId): void;
}
