<?php

namespace LimenAi\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\Fake\FakeEmbeddingProvider;

class EmbeddingProviderManager
{
    /** @var array<string, EmbeddingProvider> */
    private array $drivers = [];

    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
    ) {}

    public function driver(?string $name = null): EmbeddingProvider
    {
        $name = $name ?? (string) $this->config->get('limen-ai.embeddings.default', 'fake');

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    protected function createDriver(string $name): EmbeddingProvider
    {
        $providerConfig = $this->config->get("limen-ai.embeddings.providers.{$name}");

        if (! is_array($providerConfig)) {
            throw new ProviderConfigurationException("Limen AI embedding provider [{$name}] is not configured.");
        }

        $driver = (string) ($providerConfig['driver'] ?? $name);

        return match ($driver) {
            'fake' => $this->container->make(FakeEmbeddingProvider::class),
            default => $this->createCustomDriver($driver, $name, $providerConfig),
        };
    }

    /**
     * @param  array<string, mixed>  $providerConfig
     */
    protected function createCustomDriver(string $driver, string $name, array $providerConfig): EmbeddingProvider
    {
        $class = $this->config->get("limen-ai.embeddings.drivers.{$driver}");

        if (! is_string($class) || ! class_exists($class)) {
            throw new ProviderConfigurationException("Limen AI embedding driver [{$driver}] is not supported.");
        }

        $instance = $this->container->make($class, [
            'name' => $name,
            'config' => $providerConfig,
        ]);

        if (! $instance instanceof EmbeddingProvider) {
            throw new ProviderConfigurationException("Embedding provider [{$class}] must implement EmbeddingProvider.");
        }

        return $instance;
    }
}
