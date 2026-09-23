<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Tests\TestCase;

class ConfigToolRepositoryTest extends TestCase
{
    public function test_it_resolves_example_echo_tool(): void
    {
        $repository = app(ToolRepository::class);

        $tool = $repository->find('example_echo');

        $this->assertNotNull($tool);
        $this->assertSame('example_echo', $tool->key());
        $this->assertSame('Example Echo', $tool->name());
        $this->assertFalse($tool->requiresConfirmation());
        $this->assertSame(5, $tool->timeoutSeconds());
    }

    public function test_it_returns_tools_for_agent(): void
    {
        $repository = app(ToolRepository::class);

        $tools = $repository->forAgent('example');

        $this->assertCount(1, $tools);
        $this->assertSame('example_echo', $tools[0]->key());
    }

    public function test_it_returns_empty_tools_for_unknown_agent(): void
    {
        $repository = app(ToolRepository::class);

        $this->assertSame([], $repository->forAgent('missing'));
    }
}
