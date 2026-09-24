<?php

namespace LimenAi\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Registry\LimenAiRegistry;

final class DefinitionLoader
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LimenAiRegistry $registry,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function agents(): array
    {
        return $this->mergeSection('agents', $this->registry->agents());
    }

    /** @return array<string, array<string, mixed>> */
    public function tools(): array
    {
        return $this->mergeSection('tools', $this->registry->tools());
    }

    /** @return array<string, array<string, mixed>> */
    public function skills(): array
    {
        return $this->mergeSection('skills', $this->registry->skills());
    }

    /** @return array<string, array<string, mixed>> */
    public function workflows(): array
    {
        return $this->mergeSection('workflows', $this->registry->workflows());
    }

    /** @return array<string, array<string, mixed>> */
    public function knowledgeCollections(): array
    {
        $base = $this->config->get('limen-ai.knowledge.collections', []);
        $base = is_array($base) ? $base : [];

        $fragments = $this->loadFragments('knowledge');
        $runtime = $this->registry->knowledgeCollections();

        $merged = array_merge($base, $fragments);

        foreach ($runtime as $key => $collection) {
            if (! isset($merged[$key])) {
                $merged[$key] = $collection;

                continue;
            }

            $merged[$key] = array_merge($merged[$key], $collection);
            $merged[$key]['documents'] = array_merge(
                $merged[$key]['documents'] ?? [],
                $collection['documents'] ?? [],
            );
        }

        return $merged;
    }

    /** @param  array<string, array<string, mixed>>  $runtime */
    private function mergeSection(string $section, array $runtime): array
    {
        $base = $this->config->get("limen-ai.{$section}", []);
        $base = is_array($base) ? $base : [];

        return array_merge($base, $this->loadFragments($section), $runtime);
    }

    /** @return array<string, array<string, mixed>> */
    private function loadFragments(string $section): array
    {
        if (! function_exists('config_path')) {
            return [];
        }

        $directory = config_path('limen-ai/'.$section);

        if (! is_dir($directory)) {
            return [];
        }

        $definitions = [];

        foreach (glob($directory.'/*.php') ?: [] as $file) {
            $payload = require $file;

            if (! is_array($payload)) {
                continue;
            }

            $key = (string) ($payload['key'] ?? basename($file, '.php'));
            unset($payload['key']);

            $definitions[$key] = $payload;
        }

        return $definitions;
    }
}
