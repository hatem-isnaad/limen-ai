<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Authorization\LaravelAuthorizationService;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Contracts\Tools\IdempotencyGuard;
use LimenAi\Contracts\Tools\ToolExecutor;
use LimenAi\Observability\LogAuditLogger;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\CacheIdempotencyGuard;
use LimenAi\Tools\ClassBasedToolExecutor;
use LimenAi\Tools\ToolPipeline;

class ToolPipelineBindingTest extends TestCase
{
    public function test_tool_pipeline_dependencies_are_bound(): void
    {
        $this->assertInstanceOf(ToolPipeline::class, app(ToolPipeline::class));
        $this->assertInstanceOf(LaravelAuthorizationService::class, app(AuthorizationService::class));
        $this->assertInstanceOf(ClassBasedToolExecutor::class, app(ToolExecutor::class));
        $this->assertInstanceOf(LogAuditLogger::class, app(AuditLogger::class));
        $this->assertInstanceOf(CacheIdempotencyGuard::class, app(IdempotencyGuard::class));
    }
}
