<?php

namespace LimenAi\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\Fake\FakeLlmProvider;

class LlmProviderManager
{
    /** @var array<string, LlmProvider> */
    private array $drivers = [];

    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
    ) {}

    public function driver(?string $name = null): LlmProvider
    {
        $name = $name ?? (string) $this->config->get('limen-ai.providers.default', 'fake');

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    public function defaultDriver(): LlmProvider
    {
        return $this->driver();
    }

    protected function createDriver(string $name): LlmProvider
    {
        $providerConfig = $this->config->get("limen-ai.providers.{$name}");

        if (! is_array($providerConfig)) {
            throw new ProviderConfigurationException("Limen AI provider [{$name}] is not configured.");
        }

        $driver = (string) ($providerConfig['driver'] ?? $name);

        if ($driver === 'fake') {
            return $this->container->make(FakeLlmProvider::class);
        }

        return $this->createCustomDriver($driver, $name, $providerConfig);
    }

    /**
     * @param  array<string, mixed>  $providerConfig
     */
    protected function createCustomDriver(string $driver, string $name, array $providerConfig): LlmProvider
    {
        $class = $this->config->get("limen-ai.providers.drivers.{$driver}");

        if (! is_string($class) || ! class_exists($class)) {
            throw new ProviderConfigurationException("Limen AI provider driver [{$driver}] is not supported.");
        }

        $instance = $this->container->make($class, [
            'name' => $name,
            'config' => $providerConfig,
        ]);

        if (! $instance instanceof LlmProvider) {
            throw new ProviderConfigurationException("Provider [{$class}] must implement LlmProvider.");
        }

        return $instance;
    }
}
