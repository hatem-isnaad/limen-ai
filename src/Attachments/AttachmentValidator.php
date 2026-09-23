<?php

namespace LimenAi\Attachments;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Exceptions\AttachmentValidationException;

class AttachmentValidator
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AttachmentStore $attachments,
    ) {}

    public function assertEnabled(): void
    {
        if (! $this->enabled()) {
            throw AttachmentValidationException::disabled();
        }
    }

    public function validateUpload(string $conversationId, int $sizeBytes, string $mimeType): void
    {
        $this->assertEnabled();

        $maxKb = (int) $this->config->get('limen-ai.attachments.max_size_kb', 10240);
        $maxBytes = $maxKb * 1024;

        if ($sizeBytes > $maxBytes) {
            throw AttachmentValidationException::sizeExceeded($maxKb);
        }

        $allowed = $this->allowedMimeTypes();

        if ($allowed !== [] && ! in_array($mimeType, $allowed, true)) {
            throw AttachmentValidationException::mimeNotAllowed($mimeType);
        }

        $maxCount = (int) $this->config->get('limen-ai.attachments.max_count', 5);

        if ($this->attachments->countForConversation($conversationId) >= $maxCount) {
            throw AttachmentValidationException::limitExceeded($maxCount);
        }
    }

    public function enabled(): bool
    {
        return (bool) $this->config->get('limen-ai.attachments.enabled', true);
    }

    /** @return list<string> */
    public function allowedMimeTypes(): array
    {
        return array_values((array) $this->config->get('limen-ai.attachments.allowed_mime_types', []));
    }
}
