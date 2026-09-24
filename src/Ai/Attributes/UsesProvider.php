<?php

namespace LimenAi\Ai\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class UsesProvider
{
    public function __construct(public readonly string $provider) {}
}
