<?php

namespace LimenAi\Tests;

use Illuminate\Auth\GenericUser;
use LimenAi\LimenAiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(new GenericUser(['id' => 1, 'remember_token' => null]));
    }

    protected function getPackageProviders($app): array
    {
        return [LimenAiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('limen-ai.default_agent', 'example');
        $app['config']->set('limen-ai.tools.example_echo.class', \LimenAi\Tests\Stubs\EchoTool::class);
        $app['config']->set('limen-ai.tool_pipeline.idempotency.driver', 'cache');
        $app['config']->set('limen-ai.ui.enabled', true);
        $app['config']->set('limen-ai.ui.middleware', []);

        $app->singleton(\LimenAi\Tests\Stubs\Limen\FakeShipmentService::class);
        $app['config']->set('limen-ai.tools.get_shipment_status.class', \LimenAi\Tests\Stubs\Limen\GetShipmentStatusTool::class);
        $app['config']->set('limen-ai.tools.send_customer_message.class', \LimenAi\Tests\Stubs\Limen\SendCustomerMessageTool::class);
        $app['config']->set('limen-ai.agents.example.memory.allowed_keys', ['preferred_language', 'timezone']);
        $app['config']->set('limen-ai.attachments.enabled', true);
        $app['config']->set('limen-ai.attachments.store', \LimenAi\Attachments\InMemoryAttachmentStore::class);
    }
}
