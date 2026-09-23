<?php

namespace LimenAi\Tests\Unit\Memory;

use LimenAi\Memory\MemoryScope;
use PHPUnit\Framework\TestCase;

class MemoryScopeTest extends TestCase
{
    public function test_it_exposes_expected_scope_constants(): void
    {
        $this->assertSame('user', MemoryScope::USER);
        $this->assertSame('conversation', MemoryScope::CONVERSATION);
        $this->assertSame('agent', MemoryScope::AGENT);
    }
}
