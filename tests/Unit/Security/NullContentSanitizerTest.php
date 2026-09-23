<?php

namespace LimenAi\Tests\Unit\Security;

use LimenAi\Security\NullContentSanitizer;
use PHPUnit\Framework\TestCase;

class NullContentSanitizerTest extends TestCase
{
    public function test_sanitize_returns_content_unchanged(): void
    {
        $sanitizer = new NullContentSanitizer;

        $this->assertSame('raw content', $sanitizer->sanitize('raw content'));
    }

    public function test_wrap_untrusted_returns_content_unchanged(): void
    {
        $sanitizer = new NullContentSanitizer;

        $this->assertSame('payload', $sanitizer->wrapUntrusted('payload', 'label'));
    }

    public function test_wrap_untrusted_uses_default_label(): void
    {
        $sanitizer = new NullContentSanitizer;

        $this->assertSame('payload', $sanitizer->wrapUntrusted('payload'));
    }
}
