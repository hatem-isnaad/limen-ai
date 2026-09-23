<?php

namespace LimenAi\Providers\OpenAi;

use Illuminate\Http\Client\PendingRequest;
use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Exceptions\ProviderException;
use LimenAi\Providers\Concerns\BuildsHttpClient;

class OpenAiEmbeddingProvider implements EmbeddingProvider
{
    use BuildsHttpClient;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $name,
        private readonly array $config,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function embed(array $texts): array
    {
        $this->ensureConfigured();

        if ($texts === []) {
            return [];
        }

        $response = $this->client()->post('/embeddings', [
            'model' => (string) ($this->config['model'] ?? 'text-embedding-3-small'),
            'input' => array_values($texts),
        ]);

        if ($response->failed()) {
            throw new ProviderException(sprintf(
                'OpenAI embedding request failed with status %s: %s',
                $response->status(),
                $response->body(),
            ));
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new ProviderException('OpenAI embedding response did not include data.');
        }

        usort($data, fn (array $a, array $b): int => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        return array_map(
            fn (array $row): array => is_array($row['embedding'] ?? null) ? $row['embedding'] : [],
            $data,
        );
    }

    protected function client(): PendingRequest
    {
        return $this->buildHttpClient(
            $this->config,
            'https://api.openai.com/v1',
            array_filter([
                'Authorization' => isset($this->config['api_key']) ? 'Bearer '.$this->config['api_key'] : null,
                'OpenAI-Organization' => $this->config['organization'] ?? null,
            ]),
        );
    }

    protected function ensureConfigured(): void
    {
        $this->ensureApiKey($this->config, 'OpenAI embeddings');
    }
}
