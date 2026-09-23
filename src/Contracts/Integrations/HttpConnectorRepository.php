<?php

namespace LimenAi\Contracts\Integrations;

interface HttpConnectorRepository
{
    public function find(string $key): ?HttpConnector;
}
