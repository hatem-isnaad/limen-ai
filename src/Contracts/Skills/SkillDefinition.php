<?php

namespace LimenAi\Contracts\Skills;

interface SkillDefinition
{
    public function key(): string;

    public function name(): string;

    public function instructions(): string;

    /** @return list<string> */
    public function allowedTools(): array;

    /** @return list<string> */
    public function knowledgeSources(): array;

    public function version(): string;
}
