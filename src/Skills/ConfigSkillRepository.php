<?php

namespace LimenAi\Skills;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Skills\SkillDefinition;
use LimenAi\Contracts\Skills\SkillRepository;

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

        return array_values(array_map(
            fn (string $key, array $definition): SkillDefinition => ConfigSkillDefinition::fromConfig($key, $definition),
            array_keys($skills),
            $skills,
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

            if ($skill !== null) {
                $skills[] = $skill;
            }
        }

        return $skills;
    }
}
