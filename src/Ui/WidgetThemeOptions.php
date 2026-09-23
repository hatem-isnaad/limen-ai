<?php

namespace LimenAi\Ui;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class WidgetThemeOptions
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * Theme values driven by config / .env for the chat widget.
     *
     * @return array<string, mixed>
     */
    public function fromEnvironment(): array
    {
        $theme = $this->config->get('limen-ai.ui.theme', []);

        return $this->filterEmpty([
            'preset' => $theme['preset'] ?? null,
            'mode' => $theme['mode'] ?? null,
            'allow_mode_toggle' => $theme['allow_mode_toggle'] ?? null,
            'title' => $theme['title'] ?? null,
            'subtitle' => $theme['subtitle'] ?? null,
            'welcome_message' => $theme['welcome_message'] ?? null,
            'direction' => $theme['direction'] ?? null,
            'avatar_url' => $theme['avatar_url'] ?? null,
            'user_avatar_url' => $theme['user_avatar_url'] ?? null,
            'position' => $theme['position'] ?? null,
            'radius' => $theme['radius'] ?? null,
            'font_family' => $theme['font_family'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $agentUi
     * @param  array<string, mixed>  $componentOverrides
     * @return array<string, mixed>
     */
    public function merge(array $agentUi = [], array $componentOverrides = []): array
    {
        return array_merge(
            $this->filterEmpty($agentUi),
            $this->fromEnvironment(),
            $this->filterEmpty($componentOverrides),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function filterEmpty(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }
}
