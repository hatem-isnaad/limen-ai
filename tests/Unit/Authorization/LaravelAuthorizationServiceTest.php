<?php

namespace LimenAi\Tests\Unit\Authorization;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Authorization\LaravelAuthorizationService;
use LimenAi\Authorization\NullGuestSessionValidator;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Exceptions\AgentAuthorizationException;
use LimenAi\Exceptions\RunContextAuthorizationException;
use LimenAi\Exceptions\UnauthenticatedException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ConfigToolDefinition;

class LaravelAuthorizationServiceTest extends TestCase
{
    private function agent(array $authorization = []): ConfigAgentDefinition
    {
        return ConfigAgentDefinition::fromConfig('test_agent', [
            'name' => 'Test Agent',
            'instructions' => 'Test',
            'authorization' => array_merge([
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ], $authorization),
        ]);
    }

    private function tool(array $authorization = []): ConfigToolDefinition
    {
        return ConfigToolDefinition::fromConfig('test_tool', [
            'name' => 'Test Tool',
            'class' => \LimenAi\Tests\Stubs\EchoTool::class,
            'authorization' => $authorization,
        ]);
    }

    public function test_it_blocks_unauthenticated_users_when_auth_is_required(): void
    {
        auth()->logout();

        $service = app(AuthorizationService::class);

        $this->expectException(UnauthenticatedException::class);

        $service->authorizeAgent($this->agent());
    }

    public function test_it_allows_authenticated_users_without_abilities(): void
    {
        $this->actingAs(new GenericUser(['id' => 7]));

        $service = app(AuthorizationService::class);

        $service->authorizeAgent($this->agent());

        $this->assertTrue($service->canUseAgent($this->agent()));
    }

    public function test_it_checks_gate_abilities_with_agent_subject(): void
    {
        $this->actingAs(new GenericUser(['id' => 1]));

        Gate::define('agents.use', fn ($user, ConfigAgentDefinition $agent): bool => $agent->key() === 'test_agent');

        $service = app(AuthorizationService::class);
        $agent = $this->agent(['abilities' => ['agents.use']]);

        $service->authorizeAgent($agent);

        config()->set('limen-ai.agents.blocked', [
            'name' => 'Blocked',
            'instructions' => 'Blocked',
            'authorization' => ['abilities' => ['agents.use']],
        ]);

        $blocked = ConfigAgentDefinition::fromConfig('blocked', config('limen-ai.agents.blocked'));

        $this->expectException(AgentAuthorizationException::class);
        $service->authorizeAgent($blocked);
    }

    public function test_it_checks_policy_method_when_configured(): void
    {
        $this->actingAs(new GenericUser(['id' => 1]));

        Gate::define('access', fn (): bool => true);

        $service = app(AuthorizationService::class);
        $agent = $this->agent([
            'policy' => 'test-policy',
            'policy_method' => 'access',
        ]);

        $service->authorizeAgent($agent);
        $this->assertTrue(true);
    }

    public function test_it_allows_guest_agents_with_valid_guest_token(): void
    {
        $service = new LaravelAuthorizationService(
            app('auth'),
            new NullGuestSessionValidator(),
            app('config'),
        );

        $agent = $this->agent(['guest_allowed' => true, 'required' => true]);

        $service->validateRunContext(
            RunContextData::make(['guest_token' => 'guest-abc']),
            $agent,
        );

        $this->assertTrue($service->canUseAgent($agent));
    }

    public function test_it_rejects_guest_agents_without_token(): void
    {
        $service = app(AuthorizationService::class);
        $agent = $this->agent(['guest_allowed' => true]);

        $this->expectException(UnauthenticatedException::class);

        $service->validateRunContext(RunContextData::make(), $agent);
    }

    public function test_it_rejects_mismatched_context_user_ids(): void
    {
        $this->actingAs(new GenericUser(['id' => 5]));

        $service = app(AuthorizationService::class);

        $this->expectException(RunContextAuthorizationException::class);

        $service->validateRunContext(
            RunContextData::make(['user_id' => 99]),
            $this->agent(),
        );
    }

    public function test_it_blocks_tools_when_gate_denies_ability(): void
    {
        $this->actingAs(new GenericUser(['id' => 1]));

        Gate::define('tools.use', fn (): bool => false);

        $service = app(AuthorizationService::class);

        $this->assertFalse($service->canUseTool($this->tool(['abilities' => ['tools.use']])));
    }
}
