<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use LimenAi\Contracts\Tools\IdempotencyGuard;

class CacheIdempotencyGuard implements IdempotencyGuard
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly int $ttlSeconds = 3600,
    ) {}

    public function has(string $key): bool
    {
        return $this->cache->has($this->cacheKey($key));
    }

    public function get(string $key): ?array
    {
        $value = $this->cache->get($this->cacheKey($key));

        return is_array($value) ? $value : null;
    }

    public function remember(string $key, array $result): void
    {
        $this->cache->put($this->cacheKey($key), $result, $this->ttlSeconds);
    }

    protected function cacheKey(string $key): string
    {
        return 'limen-ai:idempotency:'.$key;
    }
}
