<?php

namespace LimenAi\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Security\ContentSanitizer;

class PromptInjectionSanitizer implements ContentSanitizer
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function sanitize(string $content): string
    {
        if (! $this->enabled()) {
            return $content;
        }

        $sanitized = $content;

        foreach ($this->patterns() as $pattern) {
            $sanitized = (string) preg_replace($pattern, '[filtered]', $sanitized);
        }

        return trim($sanitized);
    }

    public function wrapUntrusted(string $content, string $label = 'untrusted'): string
    {
        if (! $this->shouldWrapUntrusted()) {
            return $this->sanitize($content);
        }

        $content = $this->sanitize($content);

        return <<<TEXT
<<<{$label}
{$content}
>>>{$label}
TEXT;
    }

    protected function enabled(): bool
    {
        return (bool) $this->config->get('limen-ai.security.injection.enabled', true);
    }

    protected function shouldWrapUntrusted(): bool
    {
        return (bool) $this->config->get('limen-ai.security.injection.wrap_untrusted', true);
    }

    /** @return list<string> */
    protected function patterns(): array
    {
        $patterns = $this->config->get('limen-ai.security.injection.patterns', []);

        return is_array($patterns) ? $patterns : [];
    }
}
