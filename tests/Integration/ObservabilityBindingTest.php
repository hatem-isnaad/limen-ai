<?php

namespace LimenAi\Tests\Integration;

use Illuminate\Support\Facades\Route;
use LimenAi\Contracts\Observability\AuditExporter;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Observability\UsageTracker;
use LimenAi\Observability\DefaultAuditExporter;
use LimenAi\Observability\LogUsageTracker;
use LimenAi\Observability\RunObservabilityReporter;
use LimenAi\Tests\TestCase;

class ObservabilityBindingTest extends TestCase
{
    public function test_observability_contracts_are_bound(): void
    {
        $this->assertInstanceOf(DefaultAuditExporter::class, app(AuditExporter::class));
        $this->assertInstanceOf(LogUsageTracker::class, app(UsageTracker::class));
        $this->assertInstanceOf(LogUsageTracker::class, app(UsageReader::class));
        $this->assertInstanceOf(RunObservabilityReporter::class, app(RunObservabilityReporter::class));
        $this->assertTrue(Route::has('limen-ai.runs.observability'));
    }
}
