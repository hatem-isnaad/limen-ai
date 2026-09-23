<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Authorization\InMemoryApprovalRepository;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Runtime\ArrayCheckpointStore;
use LimenAi\Runtime\DefaultAgentRuntime;
use LimenAi\Runtime\InMemoryRunRepository;
use LimenAi\Tests\TestCase;

class AgentRuntimeBindingTest extends TestCase
{
    public function test_runtime_contracts_are_bound(): void
    {
        $this->assertInstanceOf(DefaultAgentRuntime::class, app(AgentRuntime::class));
        $this->assertInstanceOf(InMemoryRunRepository::class, app(RunRepository::class));
        $this->assertInstanceOf(ArrayCheckpointStore::class, app(CheckpointStore::class));
        $this->assertInstanceOf(InMemoryApprovalRepository::class, app(ApprovalRepository::class));
    }
}
