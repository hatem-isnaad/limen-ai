<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Support\ResponseLanguageResolver;
use LimenAi\Tests\TestCase;

class ResponseLanguageResolverTest extends TestCase
{
    public function test_it_detects_explicit_language_requests(): void
    {
        $resolver = app(ResponseLanguageResolver::class);

        $this->assertSame('ar', $resolver->detectFromMessage('Please talk to me in Arabic'));
        $this->assertSame('en', $resolver->detectFromMessage('Can you speak English please?'));
        $this->assertSame('ar', $resolver->detectFromMessage('تحدث معي بالعربية'));
    }

    public function test_it_detects_arabic_script_and_defaults_english_for_latin_text(): void
    {
        $resolver = app(ResponseLanguageResolver::class);

        $this->assertSame('ar', $resolver->detectFromMessage('مرحبا كيف حالك؟'));
        $this->assertSame('en', $resolver->detectFromMessage('Hello there'));
    }

    public function test_it_resolves_explicit_locale_before_message_and_stored_preference(): void
    {
        $resolver = app(ResponseLanguageResolver::class);

        $this->assertSame(
            'en',
            $resolver->resolve('en', 'تحدث بالعربية', 'ar', 'ar'),
        );

        $this->assertSame(
            'ar',
            $resolver->resolve(null, 'تحدث معي بالعربية', 'en', 'en'),
        );

        $this->assertSame(
            'ar',
            $resolver->resolve(null, 'Hello', 'ar', 'en'),
        );
    }
}
