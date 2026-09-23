<?php

namespace LimenAi\Tests\Unit\Observability;

use LimenAi\Contracts\Observability\AuditExporter;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Tests\TestCase;

class AuditExporterTest extends TestCase
{
    public function test_it_exports_buffered_audit_entries_for_a_run(): void
    {
        app(AuditLogger::class)->log('agent.started', ['run_id' => 'run-a', 'agent_key' => 'example']);
        app(AuditLogger::class)->log('agent.started', ['run_id' => 'run-b', 'agent_key' => 'example']);

        $exported = app(AuditExporter::class)->export('run-a');

        $this->assertCount(1, $exported);
        $this->assertSame('agent.started', $exported[0]['action']);
        $this->assertSame('run-a', $exported[0]['context']['run_id']);
    }
}
