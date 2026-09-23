<?php

namespace LimenAi\Exceptions;

class HttpIntegrationException extends ToolException
{
    public static function connectorNotFound(string $connectorKey): self
    {
        return new self("HTTP connector [{$connectorKey}] was not found.");
    }

    public static function urlNotAllowed(string $url): self
    {
        return new self("Outbound URL is not allowed by SSRF policy: {$url}");
    }

    public static function requestFailed(string $url, int $status, string $body): self
    {
        return new self("HTTP request to [{$url}] failed with status {$status}: {$body}");
    }
}
