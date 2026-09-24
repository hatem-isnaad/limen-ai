<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Container\Container;
use LimenAi\Ai\Attributes\UsesModel;
use LimenAi\Ai\Attributes\UsesProvider;
use LimenAi\Ai\Contracts\Agent;
use LimenAi\Ai\Contracts\HasStructuredOutput;
use LimenAi\Ai\Contracts\HasTools;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Tools\Tool;
use LimenAi\Exceptions\AgentConfigurationException;
use LimenAi\Tools\RuntimeToolCatalog;
use ReflectionClass;

final class ClassAgentDefinitionFactory
{
    public function __construct(
        private readonly Container $container,
        private readonly RuntimeToolCatalog $runtimeTools,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function make(string $key, array $config): AgentDefinition
    {
        $class = $config['class'] ?? null;

        if (! is_string($class) || $class === '') {
            throw new AgentConfigurationException("Agent [{$key}] class definition is missing a class name.");
        }

        if (! class_exists($class)) {
            throw new AgentConfigurationException("Agent [{$key}] class [{$class}] does not exist.");
        }

        if (! is_subclass_of($class, Agent::class) && ! in_array(Agent::class, class_implements($class) ?: [], true)) {
            throw new AgentConfigurationException("Agent [{$key}] class [{$class}] must implement [".Agent::class.'].');
        }

        /** @var Agent $instance */
        $instance = $this->container->make($class);

        $reflection = new ReflectionClass($class);

        $modelAttribute = $reflection->getAttributes(UsesModel::class)[0] ?? null;
        $providerAttribute = $reflection->getAttributes(UsesProvider::class)[0] ?? null;

        $toolKeys = array_values($config['tools'] ?? []);

        if ($instance instanceof HasTools) {
            foreach ($instance->tools() as $tool) {
                if (is_string($tool)) {
                    $tool = $this->container->make($tool);
                }

                if (! $tool instanceof Tool) {
                    continue;
                }

                $this->runtimeTools->register($tool);
                $toolKeys[] = $tool->key();
            }

            $toolKeys = array_values(array_unique($toolKeys));
        }

        $output = $config['output'] ?? [];

        if ($instance instanceof HasStructuredOutput) {
            $output = array_merge(['format' => 'json', 'schema' => $instance->schema()], $output);
        }

        $instructions = trim((string) $instance->instructions());

        return new ClassAgentDefinition(
            key: $key,
            name: (string) ($config['name'] ?? str($key)->headline()->toString()),
            description: isset($config['description']) ? (string) $config['description'] : null,
            model: (string) ($config['model'] ?? ($modelAttribute?->newInstance()->model ?? '')),
            provider: (string) ($config['provider'] ?? ($providerAttribute?->newInstance()->provider ?? config('limen-ai.providers.default', 'fake'))),
            instructions: $instructions,
            skills: array_values($config['skills'] ?? []),
            tools: $toolKeys,
            knowledge: array_values($config['knowledge'] ?? []),
            memoryConfig: $config['memory'] ?? ['conversation' => true, 'user' => false],
            authorizationConfig: $config['authorization'] ?? [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
            outputConfig: $output,
            limits: $config['limits'] ?? [
                'max_tool_calls' => 10,
                'max_steps' => 20,
                'timeout' => 60,
            ],
            version: (string) ($config['version'] ?? '1.0.0'),
            enabled: (bool) ($config['enabled'] ?? true),
            class: $class,
        );
    }
}
