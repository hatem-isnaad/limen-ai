<?php

namespace LimenAi\Contracts\Authorization;

interface GuestSessionValidator
{
    public function isValid(?string $guestToken): bool;
}
