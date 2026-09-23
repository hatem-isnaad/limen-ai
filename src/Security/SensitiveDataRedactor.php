<?php

namespace LimenAi\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SensitiveDataRedactor
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /** @param  array<string, mixed>  $payload */
    public function redact(array $payload): array
    {
        $keys = $this->config->get('limen-ai.security.redaction.keys', []);

        if (! is_array($keys) || $keys === []) {
            return $payload;
        }

        return $this->redactArray($payload, $keys);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function redactArray(array $payload, array $keys): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            if ($this->shouldRedact((string) $key, $keys)) {
                $redacted[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redactArray($value, $keys);

                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /** @param  list<string>  $keys */
    protected function shouldRedact(string $key, array $keys): bool
    {
        foreach ($keys as $needle) {
            if (strcasecmp($key, $needle) === 0) {
                return true;
            }
        }

        return false;
    }
}
