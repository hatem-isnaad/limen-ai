<?php

namespace LimenAi\Security;

use LimenAi\Contracts\Security\ContentSanitizer;

class NullContentSanitizer implements ContentSanitizer
{
    public function sanitize(string $content): string
    {
        return $content;
    }

    public function wrapUntrusted(string $content, string $label = 'untrusted'): string
    {
        return $content;
    }
}
