<?php

namespace LimenAi\Registry;

final class LimenAiRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $agents = [];

    /** @var array<string, array<string, mixed>> */
    private array $tools = [];

    /** @var array<string, array<string, mixed>> */
    private array $skills = [];

    /** @var array<string, array<string, mixed>> */
    private array $workflows = [];

    /** @var array<string, array<string, mixed>> */
    private array $knowledgeCollections = [];

    /** @var array<string, array<string, mixed>> */
    private array $providers = [];

    /** @param  array<string, mixed>|class-string  $definition */
    public function agent(string $key, array|string $definition): self
    {
        if (is_string($definition)) {
            $definition = ['class' => $definition];
        }

        $this->agents[$key] = $definition;

        return $this;
    }

    /** @param  array<string, mixed>|class-string  $definition */
    public function tool(string $key, array|string $definition): self
    {
        if (is_string($definition)) {
            $definition = [
                'name' => str($key)->headline()->toString(),
                'description' => 'Registered tool.',
                'class' => $definition,
                'input_schema' => [],
                'authorization' => ['abilities' => []],
                'confirmation' => false,
                'timeout' => 30,
                'version' => '1.0.0',
            ];
        }

        $this->tools[$key] = $definition;

        return $this;
    }

    /** @param  array<string, mixed>  $definition */
    public function skill(string $key, array $definition): self
    {
        $this->skills[$key] = $definition;

        return $this;
    }

    /** @param  array<string, mixed>  $definition */
    public function workflow(string $key, array $definition): self
    {
        $this->workflows[$key] = $definition;

        return $this;
    }

    /** @param  array<string, mixed>  $definition */
    public function knowledge(string $collectionKey, array $definition): self
    {
        $this->knowledgeCollections[$collectionKey] = array_merge(
            $this->knowledgeCollections[$collectionKey] ?? [],
            $definition,
        );

        return $this;
    }

    public function faq(string $collectionKey, string $question, string $answer, array $metadata = []): self
    {
        $documents = $this->knowledgeCollections[$collectionKey]['documents'] ?? [];

        $documents[] = [
            'type' => 'faq',
            'question' => $question,
            'answer' => $answer,
            'content' => "Q: {$question}\nA: {$answer}",
            'metadata' => array_merge(['source' => 'faq'], $metadata),
        ];

        $this->knowledgeCollections[$collectionKey] = array_merge(
            $this->knowledgeCollections[$collectionKey] ?? [
                'name' => str($collectionKey)->headline()->toString(),
                'description' => 'Runtime FAQ collection.',
            ],
            ['documents' => $documents],
        );

        return $this;
    }

    /** @param  array<string, mixed>  $settings */
    public function provider(string $name, array $settings): self
    {
        $this->providers[$name] = $settings;

        return $this;
    }

    /** @return array<string, array<string, mixed>> */
    public function agents(): array
    {
        return $this->agents;
    }

    /** @return array<string, array<string, mixed>> */
    public function tools(): array
    {
        return $this->tools;
    }

    /** @return array<string, array<string, mixed>> */
    public function skills(): array
    {
        return $this->skills;
    }

    /** @return array<string, array<string, mixed>> */
    public function workflows(): array
    {
        return $this->workflows;
    }

    /** @return array<string, array<string, mixed>> */
    public function knowledgeCollections(): array
    {
        return $this->knowledgeCollections;
    }

    /** @return array<string, array<string, mixed>> */
    public function providers(): array
    {
        return $this->providers;
    }
}
