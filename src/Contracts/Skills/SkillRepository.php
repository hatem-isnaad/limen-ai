<?php

namespace LimenAi\Contracts\Skills;

interface SkillRepository
{
    public function find(string $key): ?SkillDefinition;

    /** @return list<SkillDefinition> */
    public function all(): array;

    /** @return list<SkillDefinition> */
    public function forAgent(string $agentKey): array;
}
