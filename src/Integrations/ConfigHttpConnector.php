<?php

namespace LimenAi\Integrations;

use LimenAi\Contracts\Integrations\HttpConnector;

final class ConfigHttpConnector implements HttpConnector
{
    /**
     * @param  array<string, mixed>  $authentication
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        private readonly string $key,
        private readonly string $baseUrl,
        private readonly array $authentication,
        private readonly array $defaultHeaders,
    ) {}

    /** @param  array<string, mixed>  $config */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            baseUrl: rtrim((string) ($config['base_url'] ?? ''), '/'),
            authentication: is_array($config['authentication'] ?? null) ? $config['authentication'] : [],
            defaultHeaders: is_array($config['headers'] ?? null) ? $config['headers'] : [],
        );
    }

    public function key(): string
    {
        return $this->key;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function authenticationConfig(): array
    {
        return $this->authentication;
    }

    public function defaultHeaders(): array
    {
        return $this->defaultHeaders;
    }
}
