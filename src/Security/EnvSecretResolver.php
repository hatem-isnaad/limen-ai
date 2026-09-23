<?php

namespace LimenAi\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Security\SecretResolver;

class EnvSecretResolver implements SecretResolver
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function resolve(string $reference): string
    {
        if (str_starts_with($reference, 'env:')) {
            return (string) env(substr($reference, 4), '');
        }

        if ($this->config->has($reference)) {
            return (string) $this->config->get($reference, '');
        }

        return $reference;
    }
}
