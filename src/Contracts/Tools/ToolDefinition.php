<?php

namespace LimenAi\Contracts\Tools;

use LimenAi\Contracts\Enableable;

interface ToolDefinition extends Enableable
{
    public function key(): string;

    public function name(): string;

    public function description(): string;

    /** @return array<string, mixed> */
    public function inputSchema(): array;

    /** @return array<string, mixed> */
    public function authorizationConfig(): array;

    public function requiresConfirmation(): bool;

    public function timeoutSeconds(): int;

    public function executorClass(): string;

    /** @return array<string, mixed> */
    public function httpIntegration(): array;

    public function version(): string;
}
