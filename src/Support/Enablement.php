<?php

namespace LimenAi\Support;

use LimenAi\Contracts\Enableable;

final class Enablement
{
    public static function isEnabled(object $subject): bool
    {
        if ($subject instanceof Enableable) {
            return $subject->isEnabled();
        }

        return true;
    }
}
