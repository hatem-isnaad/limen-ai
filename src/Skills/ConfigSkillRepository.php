<?php

namespace LimenAi\Skills;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Skills\SkillDefinition;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Support\Enablement;

class ConfigSkillRepository implements SkillRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AgentRepository $agents,
    ) {}

    public function find(string $key): ?SkillDefinition
    {
        $definition = $this->config->get("limen-ai.skills.{$key}");

        if (! is_array($definition)) {
            return null;
        }

        return ConfigSkillDefinition::fromConfig($key, $definition);
    }

    public function all(): array
    {
        $skills = $this->config->get('limen-ai.skills', []);

        if (! is_array($skills)) {
            return [];
        }

        $definitions = array_map(
            fn (string $key, array $definition): SkillDefinition => ConfigSkillDefinition::fromConfig($key, $definition),
            array_keys($skills),
            $skills,
        );

        return array_values(array_filter(
            $definitions,
            fn (SkillDefinition $skill): bool => Enablement::isEnabled($skill),
        ));
    }

    public function forAgent(string $agentKey): array
    {
        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            return [];
        }

        $skills = [];

        foreach ($agent->skills() as $skillKey) {
            $skill = $this->find($skillKey);

            if ($skill !== null && Enablement::isEnabled($skill)) {
                $skills[] = $skill;
            }
        }

        return $skills;
    }
}
