<?php

namespace LimenAi\Integrations;

use LimenAi\Contracts\Integrations\HttpConnector;
use LimenAi\Contracts\Security\SecretResolver;
use LimenAi\Knowledge\WorkflowVariableResolver;

class HttpRequestBuilder
{
    public function __construct(
        private readonly WorkflowVariableResolver $variables,
        private readonly SecretResolver $secrets,
    ) {}

    /**
     * @param  array<string, mixed>  $integration
     * @param  array<string, mixed>  $input
     * @return array{method: string, url: string, headers: array<string, string>, query: array<string, mixed>, body: array<string, mixed>|null}
     */
    public function build(HttpConnector $connector, array $integration, array $input): array
    {
        $state = ['input' => $input];
        $path = $this->variables->resolveTemplate((string) ($integration['path'] ?? ''), $state);
        $url = $connector->baseUrl().'/'.ltrim($path, '/');
        $query = $this->variables->resolveArray((array) ($integration['query'] ?? []), $state);
        $bodyConfig = $integration['body'] ?? null;
        $body = is_array($bodyConfig) ? $this->variables->resolveArray($bodyConfig, $state) : null;

        return [
            'method' => strtoupper((string) ($integration['method'] ?? 'GET')),
            'url' => $url,
            'headers' => $this->buildHeaders($connector, $integration),
            'query' => $query,
            'body' => $body,
        ];
    }

    /**
     * @param  array<string, mixed>  $integration
     * @return array<string, string>
     */
    protected function buildHeaders(HttpConnector $connector, array $integration): array
    {
        $headers = $connector->defaultHeaders();

        foreach ((array) ($integration['headers'] ?? []) as $key => $value) {
            $headers[(string) $key] = (string) $value;
        }

        $authentication = $connector->authenticationConfig();
        $type = (string) ($authentication['type'] ?? '');

        if ($type === 'bearer') {
            $token = $this->secrets->resolve((string) ($authentication['token'] ?? ''));

            if ($token !== '') {
                $headers['Authorization'] = 'Bearer '.$token;
            }
        }

        if ($type === 'header') {
            $header = (string) ($authentication['header'] ?? 'Authorization');
            $value = $this->secrets->resolve((string) ($authentication['value'] ?? ''));

            if ($value !== '') {
                $headers[$header] = $value;
            }
        }

        return $headers;
    }
}
