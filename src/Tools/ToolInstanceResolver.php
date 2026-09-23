<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Container\Container;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Exceptions\ToolException;

class ToolInstanceResolver
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function resolve(ToolDefinition $tool): object
    {
        $class = $tool->executorClass();

        if ($class === '') {
            throw new ToolException("Tool [{$tool->key()}] does not have an executor class configured.");
        }

        return $this->container->make($class);
    }
}
