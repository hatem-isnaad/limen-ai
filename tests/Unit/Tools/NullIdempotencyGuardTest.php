<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Tools\NullIdempotencyGuard;
use PHPUnit\Framework\TestCase;

class NullIdempotencyGuardTest extends TestCase
{
    public function test_it_never_reports_cached_keys(): void
    {
        $guard = new NullIdempotencyGuard;

        $this->assertFalse($guard->has('any-key'));
    }

    public function test_get_always_returns_null(): void
    {
        $guard = new NullIdempotencyGuard;

        $this->assertNull($guard->get('any-key'));
    }

    public function test_remember_is_noop(): void
    {
        $guard = new NullIdempotencyGuard;
        $guard->remember('key', ['output' => 'x']);

        $this->assertFalse($guard->has('key'));
        $this->assertNull($guard->get('key'));
    }
}
