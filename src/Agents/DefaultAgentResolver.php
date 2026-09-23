<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Exceptions\AgentConfigurationException;
use LimenAi\Exceptions\AgentNotFoundException;
use LimenAi\Providers\LlmProviderManager;
use LimenAi\Tools\ToolSchemaBuilder;

class DefaultAgentResolver implements AgentResolver
{
    /** @var array<string, ResolvedAgent> */
    private array $resolvedCache = [];

    public function __construct(
        private readonly AgentRepository $agents,
        private readonly ToolRepository $tools,
        private readonly SkillRepository $skills,
        private readonly LlmProviderManager $providers,
        private readonly InstructionComposer $instructionComposer,
        private readonly ToolSchemaBuilder $toolSchemaBuilder,
        private readonly ConfigRepository $config,
    ) {}

    public function resolve(string $agentKey): ResolvedAgent
    {
        if ($this->shouldCacheResolvedAgents() && isset($this->resolvedCache[$agentKey])) {
            return $this->resolvedCache[$agentKey];
        }

        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            throw AgentNotFoundException::forKey($agentKey);
        }

        $this->assertAgentIsConfigured($agent);

        $resolvedTools = $this->tools->forAgent($agentKey);
        $resolvedSkills = $this->skills->forAgent($agentKey);

        $resolved = new ResolvedAgent(
            definition: $agent,
            provider: $this->providers->driver($agent->provider()),
            instructions: $this->instructionComposer->compose($agent, $resolvedSkills),
            tools: $resolvedTools,
            skills: $resolvedSkills,
            limits: $this->resolveLimits($agent),
            toolSchemaBuilder: $this->toolSchemaBuilder,
        );

        if ($this->shouldCacheResolvedAgents()) {
            $this->resolvedCache[$agentKey] = $resolved;
        }

        return $resolved;
    }

    public function exists(string $agentKey): bool
    {
        return $this->agents->exists($agentKey);
    }

    protected function assertAgentIsConfigured(AgentDefinition $agent): void
    {
        if ($agent->model() === '') {
            throw new AgentConfigurationException("Agent [{$agent->key()}] is missing a model.");
        }

        if ($agent->instructions() === '') {
            throw new AgentConfigurationException("Agent [{$agent->key()}] is missing instructions.");
        }

        $providerConfig = $this->config->get("limen-ai.providers.{$agent->provider()}");

        if (! is_array($providerConfig)) {
            throw new AgentConfigurationException("Agent [{$agent->key()}] references unknown provider [{$agent->provider()}].");
        }
    }

    /** @return array<string, mixed> */
    protected function resolveLimits(AgentDefinition $agent): array
    {
        $global = $this->config->get('limen-ai.limits', []);

        return array_merge(
            is_array($global) ? $global : [],
            $agent->limits(),
        );
    }

    protected function shouldCacheResolvedAgents(): bool
    {
        return (bool) $this->config->get('limen-ai.performance.cache_resolved_agents', true);
    }
}
