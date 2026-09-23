<?php

namespace LimenAi\Tests\Unit\Authorization;

use Illuminate\Support\Facades\Cache;
use LimenAi\Authorization\CacheGuestSessionValidator;
use LimenAi\Tests\TestCase;

class CacheGuestSessionValidatorTest extends TestCase
{
    public function test_it_rejects_null_or_empty_tokens(): void
    {
        $validator = new CacheGuestSessionValidator(Cache::store());

        $this->assertFalse($validator->isValid(null));
        $this->assertFalse($validator->isValid(''));
    }

    public function test_it_rejects_unknown_tokens(): void
    {
        $validator = new CacheGuestSessionValidator(Cache::store());

        $this->assertFalse($validator->isValid('unknown-'.uniqid()));
    }

    public function test_it_accepts_cached_guest_tokens(): void
    {
        $token = 'guest-'.uniqid();
        Cache::put('limen-ai:guest:'.$token, true, 60);

        $validator = new CacheGuestSessionValidator(Cache::store());

        $this->assertTrue($validator->isValid($token));
    }

    public function test_it_supports_custom_prefix(): void
    {
        $token = 'guest-'.uniqid();
        Cache::put('custom:prefix:'.$token, true, 60);

        $validator = new CacheGuestSessionValidator(Cache::store(), 'custom:prefix:');

        $this->assertTrue($validator->isValid($token));
    }
}
