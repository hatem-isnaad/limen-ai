<?php

namespace LimenAi\Contracts\Agents;

interface AgentDefinition
{
    public function key(): string;

    public function name(): string;

    public function description(): ?string;

    public function model(): string;

    public function provider(): string;

    public function instructions(): string;

    /** @return list<string> */
    public function skills(): array;

    /** @return list<string> */
    public function tools(): array;

    /** @return list<string> */
    public function knowledge(): array;

    /** @return array<string, mixed> */
    public function memoryConfig(): array;

    /** @return array<string, mixed> */
    public function personaConfig(): array;

    /** @return array<string, mixed> */
    public function authorizationConfig(): array;

    /** @return array<string, mixed> */
    public function outputConfig(): array;

    /** @return array<string, mixed> */
    public function limits(): array;

    public function version(): string;
}
