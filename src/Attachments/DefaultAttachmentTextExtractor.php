<?php

namespace LimenAi\Attachments;

use LimenAi\Contracts\Attachments\AttachmentTextExtractor;

class DefaultAttachmentTextExtractor implements AttachmentTextExtractor
{
    /** @var list<string> */
    private const SUPPORTED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'text/csv',
        'application/json',
        'application/xml',
        'text/xml',
    ];

    public function supports(string $mimeType, string $originalName): bool
    {
        if (in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            return true;
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return in_array($extension, ['txt', 'md', 'csv', 'json', 'xml', 'log'], true);
    }

    public function extract(string $contents, string $mimeType, string $originalName): string
    {
        if (! $this->supports($mimeType, $originalName)) {
            return '';
        }

        if ($mimeType === 'application/json') {
            $decoded = json_decode($contents, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $contents;
            }
        }

        return trim($contents);
    }
}
