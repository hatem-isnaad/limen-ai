<?php

namespace LimenAi\Authorization;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use LimenAi\Contracts\Authorization\GuestSessionValidator;

class CacheGuestSessionValidator implements GuestSessionValidator
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly string $prefix = 'limen-ai:guest:',
    ) {}

    public function isValid(?string $guestToken): bool
    {
        if ($guestToken === null || $guestToken === '') {
            return false;
        }

        return $this->cache->has($this->prefix.$guestToken);
    }
}
