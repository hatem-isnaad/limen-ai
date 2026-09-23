<?php

namespace LimenAi\Contracts\Integrations;

interface HttpConnector
{
    public function key(): string;

    public function baseUrl(): string;

    /** @return array<string, mixed> */
    public function authenticationConfig(): array;

    /** @return array<string, string> */
    public function defaultHeaders(): array;
}
