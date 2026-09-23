<?php

namespace LimenAi\Tests\Unit\Security;

use LimenAi\Contracts\Security\UrlValidator;
use LimenAi\Tests\TestCase;

class SsrfUrlValidatorTest extends TestCase
{
    public function test_it_allows_public_https_urls(): void
    {
        $validator = app(UrlValidator::class);

        $this->assertTrue($validator->isAllowed('https://api.example.com/status/123'));
    }

    public function test_it_blocks_localhost_urls(): void
    {
        $validator = app(UrlValidator::class);

        $this->assertFalse($validator->isAllowed('http://localhost/admin'));
    }

    public function test_it_blocks_private_ip_addresses(): void
    {
        $validator = app(UrlValidator::class);

        $this->assertFalse($validator->isAllowed('http://127.0.0.1/internal'));
        $this->assertFalse($validator->isAllowed('http://192.168.0.10/resource'));
    }

    public function test_it_enforces_allowed_domain_allowlist_when_configured(): void
    {
        config()->set('limen-ai.security.ssrf.allowed_domains', ['example.com']);

        $validator = app(UrlValidator::class);

        $this->assertTrue($validator->isAllowed('https://api.example.com/status'));
        $this->assertFalse($validator->isAllowed('https://evil.test/status'));
    }
}
