<?php

namespace LimenAi\Contracts\Security;

interface SecretResolver
{
    public function resolve(string $reference): string;
}
