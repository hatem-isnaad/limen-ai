<?php

namespace LimenAi\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Security\UrlValidator;

class SsrfUrlValidator implements UrlValidator
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function isAllowed(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return false;
        }

        if ($this->isBlockedHost($host)) {
            return false;
        }

        if (! $this->isAllowedHost($host)) {
            return false;
        }

        if ($this->shouldBlockPrivateIps() && filter_var($host, FILTER_VALIDATE_IP)) {
            return ! $this->isPrivateIp($host);
        }

        return true;
    }

    protected function isBlockedHost(string $host): bool
    {
        foreach ($this->blockedDomains() as $blocked) {
            if ($host === strtolower($blocked) || str_ends_with($host, '.'.strtolower($blocked))) {
                return true;
            }
        }

        return in_array($host, ['localhost', 'metadata.google.internal', 'metadata.google'], true);
    }

    protected function isAllowedHost(string $host): bool
    {
        $allowed = $this->allowedDomains();

        if ($allowed === []) {
            return true;
        }

        foreach ($allowed as $domain) {
            $domain = strtolower($domain);

            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldBlockPrivateIps(): bool
    {
        return (bool) $this->config->get('limen-ai.security.ssrf.block_private_ips', true);
    }

    /** @return list<string> */
    protected function allowedDomains(): array
    {
        $domains = $this->config->get('limen-ai.security.ssrf.allowed_domains', []);

        return is_array($domains) ? $domains : [];
    }

    /** @return list<string> */
    protected function blockedDomains(): array
    {
        $domains = $this->config->get('limen-ai.security.ssrf.blocked_domains', []);

        return is_array($domains) ? $domains : [];
    }

    protected function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return str_starts_with($ip, 'fc') || str_starts_with($ip, 'fd') || $ip === '::1';
        }

        return true;
    }
}
