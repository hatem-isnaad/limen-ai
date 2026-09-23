<?php

namespace LimenAi\Contracts\Memory;

interface MemoryStore
{
    public function put(string $scope, string $key, mixed $value, array $context = []): void;

    public function get(string $scope, string $key, array $context = []): mixed;

    public function forget(string $scope, string $key, array $context = []): void;

    /** @return list<array<string, mixed>> */
    public function all(string $scope, array $context = []): array;
}
