<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class ChecklistCommandTest extends TestCase
{
    public function test_it_prints_checklist_items(): void
    {
        $this->artisan('limen-ai:checklist')
            ->expectsOutputToContain('Limen AI first-run checklist')
            ->expectsOutputToContain('Configuration published')
            ->expectsOutputToContain('Persistence uses database');
    }
}
