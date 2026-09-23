<?php

namespace LimenAi\Support;

use Illuminate\Contracts\Foundation\Application;
use LimenAi\Authorization\DatabaseApprovalRepository;
use LimenAi\Authorization\InMemoryApprovalRepository;
use LimenAi\Conversations\DatabaseConversationRepository;
use LimenAi\Conversations\DatabaseMessageRepository;
use LimenAi\Conversations\InMemoryConversationRepository;
use LimenAi\Conversations\InMemoryMessageRepository;
use LimenAi\Runtime\ArrayCheckpointStore;
use LimenAi\Runtime\DatabaseCheckpointStore;
use LimenAi\Runtime\DatabaseRunRepository;
use LimenAi\Runtime\InMemoryRunRepository;

final class PersistenceConfig
{
    public const DRIVER_MEMORY = 'memory';

    public const DRIVER_DATABASE = 'database';

    public static function driver(?Application $app = null): string
    {
        return self::resolveDriver(null, true, null, $app);
    }

    public static function resolveDriver(
        ?string $configured = null,
        bool $autoDetect = true,
        ?bool $conversationsTableExists = null,
        ?Application $app = null,
    ): string {
        if (is_string($configured) && $configured !== '') {
            return self::normalizeDriver($configured);
        }

        $explicit = env('LIMEN_AI_PERSISTENCE_DRIVER');

        if (is_string($explicit) && $explicit !== '') {
            return self::normalizeDriver($explicit);
        }

        if ($autoDetect) {
            $tableExists = $conversationsTableExists ?? self::databaseTablesPresent($app);

            if ($tableExists) {
                return self::DRIVER_DATABASE;
            }
        }

        return self::DRIVER_MEMORY;
    }

    public static function explicitDriverConfigured(): bool
    {
        $explicit = env('LIMEN_AI_PERSISTENCE_DRIVER');

        return is_string($explicit) && $explicit !== '';
    }

    public static function conversationRepositoryClass(?string $driver = null): string
    {
        return self::usesDatabase($driver)
            ? DatabaseConversationRepository::class
            : InMemoryConversationRepository::class;
    }

    public static function messageRepositoryClass(?string $driver = null): string
    {
        return self::usesDatabase($driver)
            ? DatabaseMessageRepository::class
            : InMemoryMessageRepository::class;
    }

    public static function runRepositoryClass(?string $driver = null): string
    {
        return self::usesDatabase($driver)
            ? DatabaseRunRepository::class
            : InMemoryRunRepository::class;
    }

    public static function checkpointStoreClass(?string $driver = null): string
    {
        return self::usesDatabase($driver)
            ? DatabaseCheckpointStore::class
            : ArrayCheckpointStore::class;
    }

    public static function approvalRepositoryClass(?string $driver = null): string
    {
        return self::usesDatabase($driver)
            ? DatabaseApprovalRepository::class
            : InMemoryApprovalRepository::class;
    }

    public static function isInMemoryClass(string $class): bool
    {
        return in_array($class, [
            InMemoryConversationRepository::class,
            InMemoryMessageRepository::class,
            InMemoryRunRepository::class,
            ArrayCheckpointStore::class,
            InMemoryApprovalRepository::class,
        ], true);
    }

    protected static function usesDatabase(?string $driver = null): bool
    {
        return ($driver ?? self::driver()) === self::DRIVER_DATABASE;
    }

    protected static function normalizeDriver(string $driver): string
    {
        return in_array($driver, [self::DRIVER_MEMORY, self::DRIVER_DATABASE], true)
            ? $driver
            : self::DRIVER_MEMORY;
    }

    protected static function databaseTablesPresent(?Application $app = null): bool
    {
        try {
            $app ??= function_exists('app') ? app() : null;

            if (! $app instanceof Application || ! $app->bound('db')) {
                return false;
            }

            return $app->make('db')->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations');
        } catch (\Throwable) {
            return false;
        }
    }
}
