<?php

namespace LimenAi\Tests\Unit\Tools;

use Illuminate\Support\Facades\Gate;
use LimenAi\Exceptions\ToolAuthorizationException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tools\ToolPipeline;
use LimenAi\Tests\TestCase;

class ToolPipelineAuthorizationTest extends TestCase
{
    public function test_it_blocks_tools_when_gate_denies_ability(): void
    {
        config()->set('limen-ai.authorization.mode', 'gates');
        config()->set('limen-ai.tools.example_echo.authorization.abilities', ['tools.use']);

        Gate::define('tools.use', fn (): bool => false);

        $this->expectException(ToolAuthorizationException::class);

        app(ToolPipeline::class)->execute('example_echo', [
            'message' => 'blocked',
        ], RunContextData::make());
    }
}
