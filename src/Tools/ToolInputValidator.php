<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Exceptions\ToolValidationException;

class ToolInputValidator
{
    public function __construct(
        private readonly ValidationFactory $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validate(ToolDefinition $tool, array $input): array
    {
        [$rules, $attributes] = $this->buildRules($tool->inputSchema());

        $validation = $this->validator->make($input, $rules, [], $attributes);

        if ($validation->fails()) {
            throw new ToolValidationException(
                "Tool [{$tool->key()}] input validation failed.",
                $validation->errors()->toArray(),
            );
        }

        return $validation->validated();
    }

    /**
     * @param  array<string, mixed>  $inputSchema
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    protected function buildRules(array $inputSchema): array
    {
        $rules = [];
        $attributes = [];

        foreach ($inputSchema as $field => $definition) {
            $fieldRules = [];

            if (! is_array($definition)) {
                $rules[$field] = 'required|string';
                $attributes[$field] = (string) $field;

                continue;
            }

            $type = $definition['type'] ?? 'string';
            $fieldRules[] = match ($type) {
                'integer', 'int' => 'integer',
                'number', 'float' => 'numeric',
                'boolean', 'bool' => 'boolean',
                'array' => 'array',
                default => 'string',
            };

            if (($definition['required'] ?? false) === true) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if (isset($definition['enum']) && is_array($definition['enum'])) {
                $fieldRules[] = 'in:'.implode(',', array_map(strval(...), $definition['enum']));
            }

            $rules[$field] = $fieldRules;
            $attributes[$field] = (string) ($definition['label'] ?? $field);
        }

        return [$rules, $attributes];
    }
}
