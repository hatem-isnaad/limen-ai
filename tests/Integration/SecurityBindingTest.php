<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Security\ContentSanitizer;
use LimenAi\Security\PromptInjectionSanitizer;
use LimenAi\Tests\TestCase;

class SecurityBindingTest extends TestCase
{
    public function test_content_sanitizer_is_bound(): void
    {
        $this->assertInstanceOf(PromptInjectionSanitizer::class, app(ContentSanitizer::class));
    }
}
