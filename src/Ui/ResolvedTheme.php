<?php

namespace LimenAi\Ui;

final class ResolvedTheme
{
    public function __construct(
        private readonly array $values,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function mode(): string
    {
        return (string) $this->get('mode', 'light');
    }

    public function direction(): string
    {
        return (string) $this->get('direction', 'ltr');
    }

    public function preset(): string
    {
        return (string) $this->get('preset', 'default');
    }

    public function allowsModeToggle(): bool
    {
        return (bool) $this->get('allow_mode_toggle', false);
    }

    public function usesClientColorMode(): bool
    {
        return $this->mode() === 'auto' || $this->allowsModeToggle();
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function cssVariables(): string
    {
        $variables = [
            '--limen-ai-primary' => $this->get('primary', '#4F46E5'),
            '--limen-ai-background' => $this->get('background', '#FFFFFF'),
            '--limen-ai-text' => $this->get('text', '#111827'),
            '--limen-ai-surface' => $this->get('surface', '#F9FAFB'),
            '--limen-ai-border' => $this->get('border', 'rgba(17, 24, 39, 0.08)'),
            '--limen-ai-muted' => $this->get('muted', 'rgba(17, 24, 39, 0.65)'),
            '--limen-ai-radius' => $this->get('radius', '12px'),
            '--limen-ai-font-family' => $this->get('font_family', 'ui-sans-serif, system-ui, sans-serif'),
        ];

        return collect($variables)
            ->map(fn (mixed $value, string $name): string => $name.': '.(string) $value)
            ->implode('; ');
    }

    public function layoutStyle(): string
    {
        $parts = [
            '--limen-ai-radius: '.(string) $this->get('radius', '12px'),
            'font-family: '.(string) $this->get('font_family', 'ui-sans-serif, system-ui, sans-serif'),
        ];

        if (! $this->usesClientColorMode()) {
            $parts[] = $this->cssVariables();
        }

        return implode('; ', $parts);
    }
}
