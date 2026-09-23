<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class ValidateCommandPersistenceTest extends TestCase
{
    public function test_it_prints_persistence_mode_in_output(): void
    {
        $this->artisan('limen-ai:validate')
            ->expectsOutputToContain('Persistence mode')
            ->assertSuccessful();
    }
}
