<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Container\Container;
use LimenAi\Contracts\Integrations\HttpToolExecutor;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\Tool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolExecutor;
use LimenAi\Exceptions\ToolDisabledException;
use LimenAi\Exceptions\ToolException;
use LimenAi\Support\Enablement;

class ClassBasedToolExecutor implements ToolExecutor
{
    public function __construct(
        private readonly Container $container,
        private readonly HttpToolExecutor $httpTools,
    ) {}

    public function execute(ToolDefinition $tool, array $input, ToolExecutionContext $context): array
    {
        if (! Enablement::isEnabled($tool)) {
            throw ToolDisabledException::forTool($tool->key());
        }

        if ($tool->httpIntegration() !== []) {
            return $this->httpTools->execute(
                array_merge($tool->httpIntegration(), [
                    'tool_key' => $tool->key(),
                    'timeout' => $tool->timeoutSeconds(),
                ]),
                $input,
                $context,
            );
        }

        $class = $tool->executorClass();

        if ($class === '') {
            throw new ToolException("Tool [{$tool->key()}] does not have an executor class configured.");
        }

        $instance = $this->container->make($class);

        if (! Enablement::isEnabled($instance)) {
            throw ToolDisabledException::forTool($tool->key());
        }

        if ($instance instanceof Tool) {
            return $instance->handle($input, $context);
        }

        if (is_callable([$instance, 'handle'])) {
            return $instance->handle($input, $context);
        }

        throw new ToolException("Tool executor [{$class}] must implement handle().");
    }
}
