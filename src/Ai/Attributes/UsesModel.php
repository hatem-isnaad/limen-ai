<?php

namespace LimenAi\Ai\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class UsesModel
{
    public function __construct(public readonly string $model) {}
}
