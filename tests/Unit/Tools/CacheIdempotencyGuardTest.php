<?php

namespace LimenAi\Tests\Unit\Tools;

use Illuminate\Support\Facades\Cache;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\CacheIdempotencyGuard;

class CacheIdempotencyGuardTest extends TestCase
{
    public function test_has_returns_false_for_missing_key(): void
    {
        $guard = new CacheIdempotencyGuard(Cache::store(), 3600);

        $this->assertFalse($guard->has('missing-'.uniqid()));
    }

    public function test_remember_and_get_round_trip(): void
    {
        $guard = new CacheIdempotencyGuard(Cache::store(), 3600);
        $key = 'exec-'.uniqid();
        $payload = ['status' => 'ok', 'output' => ['message' => 'hello']];

        $guard->remember($key, $payload);

        $this->assertTrue($guard->has($key));
        $this->assertSame($payload, $guard->get($key));
    }

    public function test_get_returns_null_for_non_array_cache_value(): void
    {
        Cache::put('limen-ai:idempotency:bad-value', 'not-an-array', 60);

        $guard = new CacheIdempotencyGuard(Cache::store(), 3600);

        $this->assertNull($guard->get('bad-value'));
    }

    public function test_keys_are_namespaced(): void
    {
        $guard = new CacheIdempotencyGuard(Cache::store(), 3600);
        $key = 'namespaced-'.uniqid();

        $guard->remember($key, ['ok' => true]);

        $this->assertTrue(Cache::has('limen-ai:idempotency:'.$key));
    }
}
