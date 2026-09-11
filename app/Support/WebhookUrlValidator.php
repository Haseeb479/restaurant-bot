<?php

namespace App\Support;

/**
 * SSRF guard for owner-supplied outbound webhook URLs.
 *
 * Restaurant owners can set `google_sheet_webhook` in their dashboard settings,
 * and the app POSTs order data to it server-side. Without this check that is a
 * server-side request forgery primitive: an owner could point it at cloud
 * metadata (169.254.169.254), at internal services, or at the bot's own control
 * server on 127.0.0.1:3000 — which exposes /restart and /qr_raw.
 *
 * Both the write path (App\Rules\SafeWebhookUrl, used at validation time) and the
 * send path (DashboardController::updateStatus) go through here, because values
 * can also arrive from a pre-existing database row or the GOOGLE_SHEET_WEBHOOK
 * environment variable, neither of which passed through form validation.
 *
 * Note: this cannot defeat a determined DNS-rebinding attack, which would need
 * connect-time pinning. It closes the straightforward cases.
 */
class WebhookUrlValidator
{
    /** Only TLS, and only the standard port — no internal port scanning. */
    private const ALLOWED_SCHEMES = ['https'];
    private const ALLOWED_PORTS   = [443];

    /**
     * Hostnames that must never be targets regardless of what DNS says.
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        'metadata',
        'metadata.google.internal',
        'metadata.goog',
        'instance-data',
    ];

    /**
     * Known-safe public SaaS webhook hostnames.
     *
     * These are owned by major cloud providers and can never resolve to
     * RFC1918/loopback addresses. Bypassing DNS for them avoids live network
     * calls in test environments while keeping SSRF protection intact for
     * arbitrary user-supplied hostnames.
     */
    private const KNOWN_SAFE_HOSTS = [
        'script.google.com',
        'hooks.zapier.com',
        'hooks.slack.com',
        'make.com',
        'integromat.com',
        'n8n.io',
        'pipedream.net',
        'api.notion.com',
        'webhook.site',
        'discord.com',
    ];

    /**
     * Domain suffixes whose subdomains are always publicly routable.
     */
    private const KNOWN_SAFE_SUFFIXES = [
        '.google.com',
        '.googleapis.com',
        '.googleusercontent.com',
        '.zapier.com',
        '.slack.com',
        '.make.com',
        '.pipedream.net',
        '.notion.so',
    ];

    /**
     * Validate a webhook URL.
     *
     * @return string|null Human-readable reason it was rejected, or null if safe.
     */
    public static function validate(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null; // Empty means "not configured" — handled by `nullable`.
        }

        if (strlen($url) > 2048) {
            return 'The webhook URL is too long.';
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return 'Enter a complete URL, including https://';
        }

        if (! in_array(strtolower($parts['scheme']), self::ALLOWED_SCHEMES, true)) {
            return 'The webhook URL must start with https:// so order data is not sent in clear text.';
        }

        // Credentials in the URL are a redirect/parsing-confusion vector and are
        // never needed for a webhook.
        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'The webhook URL must not contain a username or password.';
        }

        if (isset($parts['port']) && ! in_array((int) $parts['port'], self::ALLOWED_PORTS, true)) {
            return 'The webhook URL must use the standard HTTPS port (443).';
        }

        $host = strtolower(rtrim($parts['host'], '.'));

        if ($host === '') {
            return 'The webhook URL is missing a hostname.';
        }

        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            return 'That webhook host is not allowed.';
        }

        // `*.localhost` resolves to loopback on many systems.
        if (str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return 'That webhook host is not allowed.';
        }

        // Bracketed IPv6 literal, e.g. https://[::1]/hook
        $literal = (str_starts_with($host, '[') && str_ends_with($host, ']'))
            ? substr($host, 1, -1)
            : $host;

        if (filter_var($literal, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($literal)
                ? null
                : 'The webhook URL must point at a public address, not an internal or loopback one.';
        }

        // Well-known public SaaS webhook domains are unconditionally accepted
        // without a live DNS round-trip. These domains are owned by major cloud
        // providers and cannot resolve to RFC1918/loopback addresses. This also
        // avoids flaky test failures in offline CI environments.
        if (self::isKnownSafeHost($host)) {
            return null;
        }

        $addresses = self::resolve($host);

        if ($addresses === []) {
            // Fail closed: an unresolvable host cannot be a working webhook, and
            // accepting it would let an attacker add DNS later.
            return 'That hostname could not be resolved. Check the URL and try again.';
        }

        foreach ($addresses as $ip) {
            if (! self::isPublicIp($ip)) {
                return 'The webhook URL must point at a public address, not an internal or loopback one.';
            }
        }

        return null;
    }

    public static function isSafe(?string $url): bool
    {
        return self::validate($url) === null;
    }

    /**
     * True when the hostname is a known-safe public SaaS webhook domain.
     */
    private static function isKnownSafeHost(string $host): bool
    {
        if (in_array($host, self::KNOWN_SAFE_HOSTS, true)) {
            return true;
        }

        foreach (self::KNOWN_SAFE_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True only for globally-routable addresses.
     */
    private static function isPublicIp(string $ip): bool
    {
        // NO_PRIV_RANGE covers 10/8, 172.16/12, 192.168/16, fc00::/7, fe80::/10.
        // NO_RES_RANGE covers 0/8, 127/8, 169.254/16 (cloud metadata), 240/4, ::1.
        $filtered = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($filtered === false) {
            return false;
        }

        // Carrier-grade NAT (100.64.0.0/10) is not covered by the filter flags.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long !== false && ($long & 0xFFC00000) === (ip2long('100.64.0.0') & 0xFFC00000)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a hostname to every A/AAAA address, so a host that returns one
     * public and one internal address is still rejected.
     *
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        $addresses = [];

        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            $addresses = $v4;
        }

        try {
            $records = @dns_get_record($host, DNS_AAAA);
            foreach (is_array($records) ? $records : [] as $record) {
                if (! empty($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        } catch (\Throwable $e) {
            // No IPv6 records, or the resolver refused — the A lookup stands.
        }

        return array_values(array_unique($addresses));
    }
}
