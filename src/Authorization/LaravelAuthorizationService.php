<?php

namespace LimenAi\Authorization;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Gate;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Exceptions\AgentAuthorizationException;
use LimenAi\Exceptions\ToolAuthorizationException;

class LaravelAuthorizationService implements AuthorizationService
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {}

    public function isAuthenticated(): bool
    {
        return $this->auth->check();
    }

    public function currentUserId(): ?int
    {
        $id = $this->auth->id();

        return $id === null ? null : (int) $id;
    }

    public function canUseAgent(AgentDefinition $agent): bool
    {
        $config = $agent->authorizationConfig();

        if (($config['required'] ?? true) && ! $this->isAuthenticated() && ! ($config['guest_allowed'] ?? false)) {
            return false;
        }

        return $this->hasAbilities($config['abilities'] ?? []);
    }

    public function canUseTool(ToolDefinition $tool): bool
    {
        return $this->hasAbilities($tool->authorizationConfig()['abilities'] ?? []);
    }

    public function authorizeAgent(AgentDefinition $agent): void
    {
        if (! $this->canUseAgent($agent)) {
            throw AgentAuthorizationException::forAgent($agent->key());
        }
    }

    public function authorizeTool(ToolDefinition $tool): void
    {
        if (! $this->canUseTool($tool)) {
            throw ToolAuthorizationException::forTool($tool->key());
        }
    }

    public function isGuestAllowed(AgentDefinition $agent): bool
    {
        return (bool) ($agent->authorizationConfig()['guest_allowed'] ?? false);
    }

    /** @param  list<string>  $abilities */
    protected function hasAbilities(array $abilities): bool
    {
        if ($abilities === []) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (! Gate::allows($ability)) {
                return false;
            }
        }

        return true;
    }
}
