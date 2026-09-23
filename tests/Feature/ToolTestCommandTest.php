<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class ToolTestCommandTest extends TestCase
{
    public function test_it_executes_a_tool_from_cli(): void
    {
        $this->artisan('limen-ai:tool:test', [
            'tool' => 'example_echo',
            '--input' => '{"message":"hello"}',
        ])
            ->expectsOutputToContain('"message": "hello"')
            ->assertSuccessful();
    }
}
