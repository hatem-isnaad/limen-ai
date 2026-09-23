<?php

namespace LimenAi\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

class ResponseLanguageResolver
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function resolve(
        ?string $explicit,
        ?string $userMessage = null,
        ?string $stored = null,
        ?string $acceptLanguage = null,
    ): string {
        if (($locale = $this->normalize($explicit)) !== null) {
            return $locale;
        }

        if ($userMessage !== null && ($requested = $this->detectExplicitRequest($userMessage)) !== null) {
            return $requested;
        }

        if (($locale = $this->normalize($stored)) !== null) {
            return $locale;
        }

        if ($userMessage !== null && ($inferred = $this->detectFromMessage($userMessage)) !== null) {
            return $inferred;
        }

        if ($acceptLanguage !== null && ($locale = $this->fromAcceptLanguage($acceptLanguage)) !== null) {
            return $locale;
        }

        return $this->normalize((string) $this->config->get('limen-ai.quality.default_language', 'en')) ?? 'en';
    }

    public function detectExplicitRequest(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/\b(talk|speak|reply|respond|write|chat)\s+(to\s+me\s+)?in\s+(arabic|english)\b/u', $normalized)) {
            return str_contains($normalized, 'arabic') ? 'ar' : 'en';
        }

        if (preg_match('/\b(use|switch\s+to)\s+(arabic|english)\b/u', $normalized)) {
            return str_contains($normalized, 'arabic') ? 'ar' : 'en';
        }

        if (preg_match('/(?:بالعربية|بالعربي|عربي|تحدث\s+بالعربية|تكلم\s+عربي)/u', $message)) {
            return 'ar';
        }

        if (preg_match('/(?:بالانجليزية|بالإنجليزية|انجليزي|إنجليزي|in\s+english)/ui', $message)) {
            return 'en';
        }

        return null;
    }

    public function detectFromMessage(string $message): ?string
    {
        if (($requested = $this->detectExplicitRequest($message)) !== null) {
            return $requested;
        }

        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $message)) {
            return 'ar';
        }

        if (preg_match('/\b[a-z]{2,}\b/i', $message)) {
            return 'en';
        }

        return null;
    }

    public function fromAcceptLanguage(?string $acceptLanguage): ?string
    {
        if ($acceptLanguage === null || trim($acceptLanguage) === '') {
            return null;
        }

        foreach (explode(',', $acceptLanguage) as $part) {
            $tag = trim(explode(';', $part)[0]);

            if ($tag === '') {
                continue;
            }

            $locale = $this->normalize($tag);

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }

    public function normalize(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        $locale = strtolower(trim(str_replace('_', '-', $locale)));

        if ($locale === '') {
            return null;
        }

        $primary = explode('-', $locale)[0];

        $supported = $this->supportedLocales();

        if (in_array($primary, $supported, true)) {
            return $primary;
        }

        return null;
    }

    public function label(string $locale): string
    {
        return match ($this->normalize($locale) ?? 'en') {
            'ar' => 'Arabic',
            default => 'English',
        };
    }

    /** @return list<string> */
    public function supportedLocales(): array
    {
        $configured = $this->config->get('limen-ai.ui.i18n.supported', ['en', 'ar']);

        if (! is_array($configured) || $configured === []) {
            return ['en', 'ar'];
        }

        $locales = [];

        foreach ($configured as $locale) {
            if (! is_string($locale)) {
                continue;
            }

            $primary = explode('-', strtolower(str_replace('_', '-', trim($locale))))[0];

            if ($primary !== '') {
                $locales[] = $primary;
            }
        }

        return array_values(array_unique($locales !== [] ? $locales : ['en', 'ar']));
    }
}
