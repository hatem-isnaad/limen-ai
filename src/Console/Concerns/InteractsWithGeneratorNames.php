<?php

namespace LimenAi\Console\Concerns;

use Illuminate\Support\Str;

trait InteractsWithGeneratorNames
{
    protected function studlyName(string $name): string
    {
        return Str::studly(str_replace(['-', '_'], ' ', $name));
    }

    protected function snakeKey(string $name): string
    {
        return Str::snake(str_replace('-', '_', $name));
    }
}
