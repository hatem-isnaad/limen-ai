<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Container\Container;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\Tool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolExecutor;
use LimenAi\Exceptions\ToolException;

class ClassBasedToolExecutor implements ToolExecutor
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function execute(ToolDefinition $tool, array $input, ToolExecutionContext $context): array
    {
        $class = $tool->executorClass();

        if ($class === '') {
            throw new ToolException("Tool [{$tool->key()}] does not have an executor class configured.");
        }

        $instance = $this->container->make($class);

        if ($instance instanceof Tool) {
            return $instance->handle($input, $context);
        }

        if (is_callable([$instance, 'handle'])) {
            return $instance->handle($input, $context);
        }

        throw new ToolException("Tool executor [{$class}] must implement handle().");
    }
}
