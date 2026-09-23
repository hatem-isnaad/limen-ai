<?php

namespace LimenAi\Support;

final class PackageVersion
{
    public const VERSION = '1.0.0';

    public static function uiVersionFile(): string
    {
        return dirname(__DIR__, 2).'/resources/views/VERSION';
    }

    public static function publishedUiVersionFile(string $basePath): string
    {
        return rtrim($basePath, '/').'/views/vendor/limen-ai/VERSION';
    }
}
