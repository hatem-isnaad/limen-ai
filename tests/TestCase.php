<?php

namespace LimenAi\Tests;

use LimenAi\LimenAiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LimenAiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('limen-ai.default_agent', 'example');
        $app['config']->set('limen-ai.tools.example_echo.class', \LimenAi\Tests\Stubs\EchoTool::class);
        $app['config']->set('limen-ai.tool_pipeline.idempotency.driver', 'cache');
    }
}
