<?php

namespace LimenAi\Tests\Unit\Ui;

use LimenAi\Tests\TestCase;
use LimenAi\Ui\WidgetThemeOptions;

class WidgetThemeOptionsTest extends TestCase
{
    public function test_environment_theme_overrides_agent_ui(): void
    {
        config()->set('limen-ai.ui.theme.title', 'From Env Title');
        config()->set('limen-ai.ui.theme.mode', 'dark');

        $merged = app(WidgetThemeOptions::class)->merge([
            'title' => 'Agent Title',
            'subtitle' => 'Agent Subtitle',
        ]);

        $this->assertSame('From Env Title', $merged['title']);
        $this->assertSame('dark', $merged['mode']);
        $this->assertSame('Agent Subtitle', $merged['subtitle']);
    }

    public function test_component_theme_overrides_environment_preset(): void
    {
        config()->set('limen-ai.ui.theme.preset', 'default');

        $merged = app(WidgetThemeOptions::class)->merge([], ['preset' => 'arabic']);

        $this->assertSame('arabic', $merged['preset']);
    }
}
