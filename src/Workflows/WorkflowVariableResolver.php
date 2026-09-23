<?php

namespace LimenAi\Workflows;

class WorkflowVariableResolver
{
    /** @param  array<string, mixed>  $state */
    public function resolve(string $reference, array $state): mixed
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        return $this->resolvePath(explode('.', $reference), $state);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    public function resolveArray(array $value, array $state): array
    {
        $resolved = [];

        foreach ($value as $key => $item) {
            if (is_string($item)) {
                $resolved[$key] = $this->resolveTemplate($item, $state);

                continue;
            }

            if (is_array($item)) {
                $resolved[$key] = $this->resolveArray($item, $state);

                continue;
            }

            $resolved[$key] = $item;
        }

        return $resolved;
    }

    /** @param  array<string, mixed>  $state */
    public function resolveTemplate(string $template, array $state): string
    {
        return (string) preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function (array $matches) use ($state): string {
            $value = $this->resolve(trim($matches[1]), $state);

            if (is_array($value)) {
                return json_encode($value, JSON_THROW_ON_ERROR);
            }

            return (string) ($value ?? '');
        }, $template);
    }

    /** @param  list<string>  $segments  @param  array<string, mixed>  $state */
    protected function resolvePath(array $segments, array $state): mixed
    {
        $current = $state;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
