<?php

namespace LimenAi\Contracts\Authorization;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Tools\ToolDefinition;

interface AuthorizationService
{
    public function isAuthenticated(): bool;

    public function currentUserId(): ?int;

    public function canUseAgent(AgentDefinition $agent): bool;

    public function canUseTool(ToolDefinition $tool): bool;

    public function authorizeAgent(AgentDefinition $agent): void;

    public function authorizeTool(ToolDefinition $tool): void;

    public function validateRunContext(RunContext $context, AgentDefinition $agent): void;

    public function isGuestAllowed(AgentDefinition $agent): bool;
}
