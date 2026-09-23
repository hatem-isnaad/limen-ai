<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\AuthorizableTool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Exceptions\ToolAuthorizationException;

class ToolInstanceAuthorizer
{
    public function __construct(
        private readonly ToolInstanceResolver $resolver,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function authorize(ToolDefinition $tool, array $input, ToolExecutionContext $context): void
    {
        if ($tool->httpIntegration() !== []) {
            return;
        }

        $instance = $this->resolver->resolve($tool);

        if (! $instance instanceof AuthorizableTool) {
            return;
        }

        if (! $instance->authorize($input, $context)) {
            throw ToolAuthorizationException::forTool($tool->key());
        }
    }
}
