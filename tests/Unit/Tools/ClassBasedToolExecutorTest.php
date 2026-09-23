<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Contracts\Integrations\HttpToolExecutor;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Exceptions\ToolException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\Stubs\EchoTool;
use LimenAi\Tools\ClassBasedToolExecutor;
use LimenAi\Tools\ConfigToolDefinition;
use LimenAi\Tests\TestCase;
use Mockery;

class ClassBasedToolExecutorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_executes_tool_class_handle_method(): void
    {
        $tool = ConfigToolDefinition::fromConfig('example_echo', [
            'name' => 'Echo',
            'description' => 'Echoes input.',
            'class' => EchoTool::class,
            'input_schema' => ['message' => ['type' => 'string', 'required' => true]],
        ]);

        $context = RunContextData::make(['user_id' => 5])->forToolExecution('run-1', 'conv-1', 'example');
        $result = app(ClassBasedToolExecutor::class)->execute($tool, ['message' => 'hello'], $context);

        $this->assertSame('hello', $result['message']);
        $this->assertSame(5, $result['user_id']);
    }

    public function test_it_delegates_to_http_executor_when_integration_configured(): void
    {
        $http = Mockery::mock(HttpToolExecutor::class);
        $http->shouldReceive('execute')
            ->once()
            ->with(
                Mockery::on(fn (array $config): bool => $config['connector'] === 'example_api'
                    && $config['tool_key'] === 'http_tool'
                    && $config['timeout'] === 15),
                ['resource' => 'shipments'],
                Mockery::type(ToolExecutionContext::class),
            )
            ->andReturn(['status' => 200, 'body' => ['ok' => true]]);

        $executor = new ClassBasedToolExecutor($http, app(\LimenAi\Tools\ToolInstanceResolver::class));

        $tool = ConfigToolDefinition::fromConfig('http_tool', [
            'name' => 'HTTP Tool',
            'description' => 'HTTP',
            'integration' => [
                'connector' => 'example_api',
                'method' => 'GET',
                'path' => '/status/{{ input.resource }}',
            ],
            'timeout' => 15,
        ]);

        $result = $executor->execute($tool, ['resource' => 'shipments'], RunContextData::make());

        $this->assertSame(200, $result['status']);
    }

    public function test_it_throws_when_executor_class_missing(): void
    {
        $tool = ConfigToolDefinition::fromConfig('broken', [
            'name' => 'Broken',
            'description' => 'No class.',
        ]);

        $this->expectException(ToolException::class);
        $this->expectExceptionMessage('does not have an executor class');

        app(ClassBasedToolExecutor::class)->execute($tool, [], RunContextData::make());
    }

    public function test_it_throws_when_executor_has_no_handle_method(): void
    {
        $tool = ConfigToolDefinition::fromConfig('broken', [
            'name' => 'Broken',
            'description' => 'Bad class.',
            'class' => \stdClass::class,
        ]);

        $this->expectException(ToolException::class);
        $this->expectExceptionMessage('must implement handle()');

        app(ClassBasedToolExecutor::class)->execute($tool, [], RunContextData::make());
    }
}
