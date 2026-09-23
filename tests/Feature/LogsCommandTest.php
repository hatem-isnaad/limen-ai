<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Observability\AuditBuffer;
use LimenAi\Tests\TestCase;

class LogsCommandTest extends TestCase
{
    public function test_it_displays_buffered_audit_entries(): void
    {
        app(AuditLogger::class)->log('tool.started', [
            'run_id' => 'run-123',
            'tool' => 'example_echo',
        ]);

        $this->artisan('limen-ai:logs', ['--run' => 'run-123'])
            ->expectsOutputToContain('tool.started')
            ->assertSuccessful();
    }

    public function test_it_warns_when_no_entries_exist(): void
    {
        app(AuditBuffer::class)->flush();

        $this->artisan('limen-ai:logs')
            ->expectsOutputToContain('No audit log entries found.')
            ->assertSuccessful();
    }
}
