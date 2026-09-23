<?php

namespace LimenAi\Contracts\Security;

interface UrlValidator
{
    public function isAllowed(string $url): bool;
}
