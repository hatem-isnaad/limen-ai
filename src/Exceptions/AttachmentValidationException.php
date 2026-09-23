<?php

namespace LimenAi\Exceptions;

class AttachmentValidationException extends SecurityException
{
    public static function disabled(): self
    {
        return new self('Attachment uploads are disabled.');
    }

    public static function limitExceeded(int $max): self
    {
        return new self("Conversation attachment limit exceeded (max {$max}).");
    }

    public static function sizeExceeded(int $maxKb): self
    {
        return new self("Attachment exceeds maximum size of {$maxKb} KB.");
    }

    public static function mimeNotAllowed(string $mime): self
    {
        return new self("Attachment mime type [{$mime}] is not allowed.");
    }

    public static function notFound(string $attachmentId): self
    {
        return new self("Attachment [{$attachmentId}] was not found.");
    }
}
