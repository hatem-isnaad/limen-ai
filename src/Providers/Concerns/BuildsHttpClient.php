<?php

namespace LimenAi\Providers\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LimenAi\Exceptions\ProviderConfigurationException;

trait BuildsHttpClient
{
    /** @param  array<string, mixed>  $config */
    protected function buildHttpClient(array $config, string $defaultBaseUrl, array $defaultHeaders = []): PendingRequest
    {
        $headers = array_merge(
            $defaultHeaders,
            is_array($config['headers'] ?? null) ? $config['headers'] : [],
        );

        $headers = array_filter($headers, fn ($value) => $value !== null && $value !== '');

        return Http::baseUrl(rtrim((string) ($config['base_url'] ?? $defaultBaseUrl), '/'))
            ->withHeaders($headers)
            ->acceptJson()
            ->timeout((int) ($config['timeout'] ?? 60));
    }

    /** @param  array<string, mixed>  $config */
    protected function ensureApiKey(array $config, string $providerLabel): void
    {
        if (empty($config['api_key'])) {
            throw new ProviderConfigurationException("{$providerLabel} provider is missing an API key.");
        }
    }
}
