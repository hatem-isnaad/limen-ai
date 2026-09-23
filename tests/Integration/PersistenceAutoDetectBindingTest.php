<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Conversations\DatabaseConversationRepository;
use LimenAi\Tests\DatabaseTestCase;

class PersistenceAutoDetectBindingTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.persistence.driver', null);
        $app['config']->set('limen-ai.persistence.auto_detect', true);
    }

    public function test_it_binds_database_repositories_when_migrations_exist(): void
    {
        $this->assertInstanceOf(DatabaseConversationRepository::class, app(ConversationRepository::class));
    }
}
