<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Authorization\DatabaseApprovalRepository;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Conversations\DatabaseConversationRepository;
use LimenAi\Conversations\DatabaseMessageRepository;
use LimenAi\Runtime\DatabaseCheckpointStore;
use LimenAi\Runtime\DatabaseRunRepository;
use LimenAi\Support\PersistenceConfig;
use LimenAi\Tests\DatabaseTestCase;

class PersistenceDriverBindingTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.persistence.driver', PersistenceConfig::DRIVER_DATABASE);
    }

    public function test_database_persistence_driver_binds_database_repositories(): void
    {
        $this->assertInstanceOf(DatabaseConversationRepository::class, app(ConversationRepository::class));
        $this->assertInstanceOf(DatabaseMessageRepository::class, app(MessageRepository::class));
        $this->assertInstanceOf(DatabaseRunRepository::class, app(RunRepository::class));
        $this->assertInstanceOf(DatabaseCheckpointStore::class, app(CheckpointStore::class));
        $this->assertInstanceOf(DatabaseApprovalRepository::class, app(ApprovalRepository::class));
    }
}
