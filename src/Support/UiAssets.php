<?php

namespace LimenAi\Support;

class UiAssets
{
    public static function css(): string
    {
        return self::read('resources/css/limen-ai/chat.css');
    }

    public static function js(): string
    {
        return self::read('resources/js/limen-ai/chat.js');
    }

    protected static function read(string $relativePath): string
    {
        $path = dirname(__DIR__, 2).'/'.$relativePath;

        if (! is_readable($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
}
