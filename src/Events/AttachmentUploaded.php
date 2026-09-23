<?php

namespace LimenAi\Events;

class AttachmentUploaded
{
    public function __construct(
        public readonly string $attachmentId,
        public readonly string $conversationId,
        public readonly string $originalName,
        public readonly ?int $userId = null,
    ) {}
}
