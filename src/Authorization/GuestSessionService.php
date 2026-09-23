<?php

namespace LimenAi\Authorization;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;

class GuestSessionService
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly GuestProfileValidator $profileValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $profile
     * @return array{guest_token: string, profile: array<string, mixed>}
     */
    public function register(array $profile): array
    {
        $validated = $this->profileValidator->validate($profile);
        $token = (string) Str::uuid();
        $prefix = (string) $this->config->get('limen-ai.authorization.guest.cache_prefix', 'limen-ai:guest:');
        $ttlMinutes = (int) $this->config->get('limen-ai.ui.guest.session_ttl_minutes', 10080);

        $this->cache->put($prefix.$token, $validated, now()->addMinutes($ttlMinutes));

        return [
            'guest_token' => $token,
            'profile' => $validated,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profile(string $guestToken): ?array
    {
        $prefix = (string) $this->config->get('limen-ai.authorization.guest.cache_prefix', 'limen-ai:guest:');

        /** @var array<string, mixed>|null $profile */
        $profile = $this->cache->get($prefix.$guestToken);

        return is_array($profile) ? $profile : null;
    }
}
