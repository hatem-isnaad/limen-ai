<?php

namespace LimenAi\Runtime;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use LimenAi\Agents\ResolvedAgent;
use LimenAi\Contracts\Runtime\AgentStepMiddleware;

final class AgentStepRunner
{
    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    public function process(ResolvedAgent $agent, string $runId, array $messages, int $step): PendingAgentStep
    {
        $stepObject = new PendingAgentStep(
            agent: $agent,
            runId: $runId,
            messages: $messages,
            options: $agent->chatOptions(),
            step: $step,
        );

        $pipeline = $this->middleware();

        if ($pipeline === []) {
            return $stepObject;
        }

        $runner = array_reduce(
            array_reverse($pipeline),
            function (callable $next, string $class): callable {
                return function (PendingAgentStep $pending) use ($class, $next): PendingAgentStep {
                    $middleware = $this->container->make($class);

                    if (! $middleware instanceof AgentStepMiddleware) {
                        return $next($pending);
                    }

                    return $middleware->handle($pending, fn (PendingAgentStep $step): PendingAgentStep => $next($step));
                };
            },
            fn (PendingAgentStep $pending): PendingAgentStep => $pending,
        );

        return $runner($stepObject);
    }

    /** @return list<class-string<AgentStepMiddleware>> */
    protected function middleware(): array
    {
        $configured = $this->config->get('limen-ai.agent_middleware', []);

        return is_array($configured) ? array_values(array_filter($configured, is_string(...))) : [];
    }
}
