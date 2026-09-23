<?php

namespace LimenAi\Contracts\Security;

interface ContentSanitizer
{
    public function sanitize(string $content): string;

    public function wrapUntrusted(string $content, string $label = 'untrusted'): string;
}
