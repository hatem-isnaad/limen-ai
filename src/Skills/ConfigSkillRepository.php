<?php

namespace LimenAi\Skills;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Skills\SkillDefinition;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Support\DefinitionLoader;
use LimenAi\Support\Enablement;

class ConfigSkillRepository implements SkillRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AgentRepository $agents,
        private readonly DefinitionLoader $definitions,
    ) {}

    public function find(string $key): ?SkillDefinition
    {
        $definition = $this->definitions->skills()[$key] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        return ConfigSkillDefinition::fromConfig($key, $definition);
    }

    public function all(): array
    {
        $skills = $this->definitions->skills();

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
