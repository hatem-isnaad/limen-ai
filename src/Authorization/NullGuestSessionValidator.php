<?php

namespace LimenAi\Authorization;

use LimenAi\Contracts\Authorization\GuestSessionValidator;

class NullGuestSessionValidator implements GuestSessionValidator
{
    public function isValid(?string $guestToken): bool
    {
        return is_string($guestToken) && $guestToken !== '';
    }
}
