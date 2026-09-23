<?php

namespace LimenAi\Integrations;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Integrations\HttpConnector;
use LimenAi\Contracts\Integrations\HttpConnectorRepository;

class ConfigHttpConnectorRepository implements HttpConnectorRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function find(string $key): ?HttpConnector
    {
        $definition = $this->config->get("limen-ai.integrations.connectors.{$key}");

        if (! is_array($definition)) {
            return null;
        }

        return ConfigHttpConnector::fromConfig($key, $definition);
    }
}
