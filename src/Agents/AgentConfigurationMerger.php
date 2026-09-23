<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

class AgentConfigurationMerger
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public function merge(array $definition): array
    {
        $defaults = $this->config->get('limen-ai.agent_defaults', []);

        if (! is_array($defaults) || $defaults === []) {
            return $definition;
        }

        return $this->deepMerge($defaults, $definition);
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function deepMerge(array $base, array $overrides): array
    {
        $merged = $base;

        foreach ($overrides as $key => $value) {
            if (is_array($value)
                && isset($merged[$key])
                && is_array($merged[$key])
                && $this->isAssociative($value)
                && $this->isAssociative($merged[$key])) {
                $merged[$key] = $this->deepMerge($merged[$key], $value);

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /** @param  array<mixed>  $array */
    protected function isAssociative(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
