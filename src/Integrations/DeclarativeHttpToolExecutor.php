<?php

namespace LimenAi\Integrations;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;
use LimenAi\Contracts\Integrations\HttpConnectorRepository;
use LimenAi\Contracts\Integrations\HttpToolExecutor;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Security\UrlValidator;
use LimenAi\Exceptions\HttpIntegrationException;
use LimenAi\Exceptions\SecurityException;

class DeclarativeHttpToolExecutor implements HttpToolExecutor
{
    public function __construct(
        private readonly HttpConnectorRepository $connectors,
        private readonly HttpRequestBuilder $requests,
        private readonly UrlValidator $urlValidator,
        private readonly ConfigRepository $config,
    ) {}

    /** @param  array<string, mixed>  $toolConfig  @param  array<string, mixed>  $input */
    public function execute(array $toolConfig, array $input, ToolExecutionContext $context): array
    {
        $connectorKey = (string) ($toolConfig['connector'] ?? '');

        if ($connectorKey === '') {
            throw HttpIntegrationException::connectorNotFound('unknown');
        }

        $connector = $this->connectors->find($connectorKey);

        if ($connector === null) {
            throw HttpIntegrationException::connectorNotFound($connectorKey);
        }

        $request = $this->requests->build($connector, $toolConfig, $input);

        try {
            $this->urlValidator->assertAllowed($request['url']);
        } catch (SecurityException $exception) {
            throw HttpIntegrationException::urlNotAllowed($request['url']);
        }

        $timeout = (int) ($toolConfig['timeout'] ?? 30);
        $client = Http::timeout($timeout)
            ->withOptions(['allow_redirects' => $this->redirectOptions()])
            ->withHeaders($request['headers']);

        $response = match ($request['method']) {
            'GET' => $client->get($request['url'], $request['query']),
            'POST' => $client->post($request['url'], $request['body'] ?? $request['query']),
            'PUT' => $client->put($request['url'], $request['body'] ?? []),
            'PATCH' => $client->patch($request['url'], $request['body'] ?? []),
            'DELETE' => $client->delete($request['url'], $request['query']),
            default => throw new HttpIntegrationException("Unsupported HTTP method [{$request['method']}]."),
        };

        if ($response->failed()) {
            throw HttpIntegrationException::requestFailed(
                $request['url'],
                $response->status(),
                $response->body(),
            );
        }

        $body = $response->json();

        if (! is_array($body)) {
            $body = ['raw' => $response->body()];
        }

        return [
            'status' => $response->status(),
            'body' => $body,
        ];
    }

    /** @return array<string, mixed>|false */
    protected function redirectOptions(): array|false
    {
        if (! (bool) $this->config->get('limen-ai.security.ssrf.allow_redirects', false)) {
            return false;
        }

        return [
            'max' => (int) $this->config->get('limen-ai.security.ssrf.max_redirects', 0),
            'strict' => true,
            'protocols' => ['http', 'https'],
        ];
    }
}
