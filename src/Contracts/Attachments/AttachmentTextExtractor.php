<?php

namespace LimenAi\Contracts\Attachments;

interface AttachmentTextExtractor
{
    public function supports(string $mimeType, string $originalName): bool;

    public function extract(string $contents, string $mimeType, string $originalName): string;
}
