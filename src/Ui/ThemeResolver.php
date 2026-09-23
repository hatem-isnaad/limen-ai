<?php

namespace LimenAi\Ui;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

class ThemeResolver
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function resolve(array $overrides = []): ResolvedTheme
    {
        $themeConfig = $this->config->get('limen-ai.ui.theme', []);
        $preset = (string) ($overrides['preset'] ?? $themeConfig['preset'] ?? 'default');
        $mode = (string) ($overrides['mode'] ?? $themeConfig['mode'] ?? 'light');

        $paletteKey = $mode === 'dark' ? 'dark' : 'light';
        $palette = $this->config->get('limen-ai.ui.palettes.'.$paletteKey, []);
        $presetTokens = $this->config->get('limen-ai.ui.presets.'.$preset, []);

        $reserved = ['preset', 'mode', 'overrides', 'allow_mode_toggle'];
        $configOverrides = array_diff_key($themeConfig, array_flip($reserved));
        $explicitOverrides = is_array($themeConfig['overrides'] ?? null) ? $themeConfig['overrides'] : [];

        $resolved = array_merge(
            $palette,
            $presetTokens,
            $configOverrides,
            $explicitOverrides,
            $overrides,
        );

        $resolved['preset'] = $preset;
        $resolved['mode'] = $mode;
        $resolved['allow_mode_toggle'] = (bool) ($overrides['allow_mode_toggle']
            ?? $themeConfig['allow_mode_toggle']
            ?? false);

        if (! isset($resolved['direction'])) {
            $resolved['direction'] = 'ltr';
        }

        return new ResolvedTheme($resolved);
    }
}
