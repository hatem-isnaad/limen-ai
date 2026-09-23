<?php

namespace LimenAi\Tests\Unit\Security;

use LimenAi\Security\SensitiveDataRedactor;
use LimenAi\Tests\TestCase;

class SensitiveDataRedactorTest extends TestCase
{
    public function test_it_redacts_configured_sensitive_keys(): void
    {
        $redacted = app(SensitiveDataRedactor::class)->redact([
            'message' => 'hello',
            'api_key' => 'secret-value',
            'nested' => [
                'token' => 'nested-secret',
            ],
        ]);

        $this->assertSame('hello', $redacted['message']);
        $this->assertSame('[redacted]', $redacted['api_key']);
        $this->assertSame('[redacted]', $redacted['nested']['token']);
    }
}
