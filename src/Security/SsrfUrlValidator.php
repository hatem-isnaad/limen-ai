<?php

namespace LimenAi\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Security\UrlValidator;
use LimenAi\Exceptions\SecurityException;

class SsrfUrlValidator implements UrlValidator
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function isAllowed(string $url): bool
    {
        try {
            $this->assertAllowed($url);

            return true;
        } catch (SecurityException) {
            return false;
        }
    }

    public function assertAllowed(string $url): void
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw SecurityException::ssrfBlocked($url, 'invalid url');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw SecurityException::ssrfBlocked($url, 'invalid scheme');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            throw SecurityException::ssrfBlocked($url, 'missing host');
        }

        if ($this->isBlockedHost($host)) {
            throw SecurityException::ssrfBlocked($url, 'blocked host');
        }

        if (! $this->isAllowedHost($host)) {
            throw SecurityException::ssrfBlocked($url, 'host not allowlisted');
        }

        if ($this->shouldBlockPrivateIps() && filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateIp($host)) {
                throw SecurityException::ssrfBlocked($url, 'private ip');
            }

            return;
        }

        if ($this->shouldResolveDns()) {
            $this->assertResolvedHostsAreSafe($url, $host);
        }
    }

    protected function assertResolvedHostsAreSafe(string $url, string $host): void
    {
        if (! $this->shouldBlockPrivateIps()) {
            return;
        }

        foreach ($this->resolveHostAddresses($host) as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw SecurityException::ssrfBlocked($url, "dns resolved to private ip {$ip}");
            }
        }
    }

    /** @return list<string> */
    protected function resolveHostAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = dns_get_record($host, DNS_A + DNS_AAAA);

        if ($records === [] || $records === false) {
            $fallback = gethostbynamel($host) ?: [];

            return array_values(array_filter($fallback, fn (string $ip): bool => $ip !== ''));
        }

        $addresses = [];

        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $addresses[] = (string) $record['ip'];
            }

            if (isset($record['ipv6'])) {
                $addresses[] = (string) $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
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

    protected function shouldResolveDns(): bool
    {
        return (bool) $this->config->get('limen-ai.security.ssrf.resolve_dns', true);
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
            $normalized = strtolower($ip);

            return str_starts_with($normalized, 'fc')
                || str_starts_with($normalized, 'fd')
                || str_starts_with($normalized, 'fe80')
                || $normalized === '::1';
        }

        return true;
    }
}
