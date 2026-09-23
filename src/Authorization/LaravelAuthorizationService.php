<?php

namespace LimenAi\Authorization;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Gate;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Authorization\GuestSessionValidator;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Exceptions\AgentAuthorizationException;
use LimenAi\Exceptions\RunContextAuthorizationException;
use LimenAi\Exceptions\ToolAuthorizationException;
use LimenAi\Exceptions\UnauthenticatedException;

class LaravelAuthorizationService implements AuthorizationService
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly GuestSessionValidator $guestSessions,
        private readonly ConfigRepository $config,
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
        $authorization = $agent->authorizationConfig();

        if ($this->authenticationRequired($authorization) && ! $this->isAuthenticated() && ! $this->isGuestAllowed($agent)) {
            return false;
        }

        return $this->passesAuthorization($authorization, $agent);
    }

    public function canUseTool(ToolDefinition $tool): bool
    {
        return $this->passesAuthorization($tool->authorizationConfig(), $tool);
    }

    public function authorizeAgent(AgentDefinition $agent): void
    {
        $authorization = $agent->authorizationConfig();

        if ($this->authenticationRequired($authorization) && ! $this->isAuthenticated() && ! $this->isGuestAllowed($agent)) {
            throw UnauthenticatedException::forAgent($agent->key());
        }

        if (! $this->passesAuthorization($authorization, $agent)) {
            throw AgentAuthorizationException::forAgent($agent->key());
        }
    }

    public function authorizeTool(ToolDefinition $tool): void
    {
        if (! $this->canUseTool($tool)) {
            throw ToolAuthorizationException::forTool($tool->key());
        }
    }

    public function validateRunContext(RunContext $context, AgentDefinition $agent): void
    {
        if (! $this->shouldEnforceContextUserMatch()) {
            return;
        }

        if ($this->isAuthenticated()) {
            $authenticatedUserId = $this->currentUserId();
            $contextUserId = $context->userId();

            if ($contextUserId !== null && $authenticatedUserId !== null && $contextUserId !== $authenticatedUserId) {
                throw RunContextAuthorizationException::userMismatch($contextUserId, $authenticatedUserId);
            }

            return;
        }

        if (! $this->isGuestAllowed($agent)) {
            return;
        }

        $guestToken = $context->guestToken();

        if ($guestToken === null || $guestToken === '') {
            throw UnauthenticatedException::guestSessionRequired($agent->key());
        }

        if (! $this->guestSessions->isValid($guestToken)) {
            throw UnauthenticatedException::guestSessionInvalid($agent->key());
        }
    }

    public function isGuestAllowed(AgentDefinition $agent): bool
    {
        return (bool) ($agent->authorizationConfig()['guest_allowed'] ?? false);
    }

    /** @param  array<string, mixed>  $authorization */
    protected function authenticationRequired(array $authorization): bool
    {
        return (bool) ($authorization['required'] ?? true);
    }

    protected function shouldEnforceContextUserMatch(): bool
    {
        return (bool) $this->config->get('limen-ai.authorization.enforce_context_user_match', true);
    }

    /** @param  array<string, mixed>  $authorization */
    protected function passesAuthorization(array $authorization, object $subject): bool
    {
        foreach ($authorization['abilities'] ?? [] as $ability) {
            if (! Gate::check((string) $ability, $subject)) {
                return false;
            }
        }

        if (isset($authorization['policy'], $authorization['policy_method']) && $this->isAuthenticated()) {
            $user = $this->auth->user();

            if ($user === null || ! Gate::forUser($user)->allows((string) $authorization['policy_method'], $subject)) {
                return false;
            }
        }

        return true;
    }
}
