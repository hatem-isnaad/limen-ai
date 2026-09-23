<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Tools\ToolDefinition;

class ToolSchemaBuilder
{
    /**
     * @param  list<ToolDefinition>  $tools
     * @return list<array<string, mixed>>
     */
    public function buildMany(array $tools): array
    {
        return array_map(
            fn (ToolDefinition $tool): array => $this->build($tool),
            $tools,
        );
    }

    /** @return array<string, mixed> */
    public function build(ToolDefinition $tool): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->key(),
                'description' => $tool->description(),
                'parameters' => $this->buildParameters($tool->inputSchema()),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputSchema
     * @return array<string, mixed>
     */
    public function buildParameters(array $inputSchema): array
    {
        $properties = [];
        $required = [];

        foreach ($inputSchema as $name => $definition) {
            if (! is_array($definition)) {
                $properties[$name] = ['type' => 'string'];
                $required[] = $name;

                continue;
            }

            $property = array_filter([
                'type' => $definition['type'] ?? 'string',
                'description' => $definition['description'] ?? null,
                'enum' => $definition['enum'] ?? null,
            ], fn ($value) => $value !== null);

            $properties[$name] = $property;

            if (($definition['required'] ?? false) === true) {
                $required[] = $name;
            }
        }

        return array_filter([
            'type' => 'object',
            'properties' => $properties,
            'required' => $required !== [] ? array_values($required) : null,
            'additionalProperties' => false,
        ], fn ($value) => $value !== null);
    }
}
