import dns from 'dns';
import net from 'net';

/**
 * SSRF guard for outbound webhook URLs — the bot-side counterpart of
 * app/Support/WebhookUrlValidator.php. The two must stay in step: the same
 * `restaurants.google_sheet_webhook` value is POSTed to from both processes, and
 * a rule enforced on only one side is not enforced at all.
 *
 * The URL is owner-supplied (dashboard Settings) or operator-supplied
 * (GOOGLE_SHEET_WEBHOOK), and the bot POSTs the customer's phone number, the
 * tracking code and a chat excerpt to it. Unchecked, that is a server-side
 * request forgery primitive from inside the host: cloud metadata
 * (169.254.169.254), internal services, or the bot's own control server on
 * 127.0.0.1:3000 — which now requires a token, but is bound to loopback
 * precisely because it is reachable from here.
 *
 * Deliberately NOT covered: DNS rebinding, which needs connect-time pinning of
 * the resolved address. This closes the straightforward cases.
 */

const ALLOWED_SCHEMES = ['https:'];
const ALLOWED_PORTS   = [443];
const MAX_URL_LENGTH  = 2048;

/** Hostnames that must never be targets regardless of what DNS says. */
const BLOCKED_HOSTS = [
    'localhost',
    'metadata',
    'metadata.google.internal',
    'metadata.goog',
    'instance-data',
];

/** Suffixes that resolve to the local network or machine on many systems. */
const BLOCKED_SUFFIXES = ['.localhost', '.local', '.internal'];

const NOT_PUBLIC = 'The webhook URL must point at a public address, not an internal or loopback one.';

/**
 * Non-globally-routable IPv4 space. Mirrors what PHP's NO_PRIV_RANGE /
 * NO_RES_RANGE filter flags cover, plus the ranges those flags miss
 * (CGNAT, benchmarking, multicast).
 */
const BLOCKED_V4 = [
    ['0.0.0.0', 8],           // "this network"
    ['10.0.0.0', 8],          // private
    ['100.64.0.0', 10],       // carrier-grade NAT
    ['127.0.0.0', 8],         // loopback
    ['169.254.0.0', 16],      // link-local — cloud metadata lives here
    ['172.16.0.0', 12],       // private
    ['192.0.0.0', 24],        // IETF protocol assignments
    ['192.168.0.0', 16],      // private
    ['198.18.0.0', 15],       // benchmarking
    ['224.0.0.0', 4],         // multicast
    ['240.0.0.0', 4],         // reserved
];

/**
 * Same for IPv6. v4-mapped (::ffff:0:0/96) and NAT64 (64:ff9b::/96) are not
 * listed: their embedded IPv4 address is extracted and checked as IPv4 instead,
 * so `::ffff:127.0.0.1` cannot slip through as "just an IPv6 address".
 */
const BLOCKED_V6 = [
    ['::', 128],              // unspecified
    ['::1', 128],             // loopback
    ['fc00::', 7],            // unique local
    ['fe80::', 10],           // link-local
    ['ff00::', 8],            // multicast
];

/**
 * Validate a webhook URL.
 *
 * @param {string|null|undefined} url
 * @returns {Promise<string|null>} Reason it was rejected, or null if safe.
 *                                 An empty URL is "not configured", not unsafe.
 */
export async function validateWebhookUrl(url) {
    const raw = String(url ?? '').trim();

    if (raw === '') {
        return null;
    }

    if (raw.length > MAX_URL_LENGTH) {
        return 'The webhook URL is too long.';
    }

    let parsed;
    try {
        parsed = new URL(raw);
    } catch {
        return 'Enter a complete URL, including https://';
    }

    if (!ALLOWED_SCHEMES.includes(parsed.protocol)) {
        return 'The webhook URL must start with https:// so order data is not sent in clear text.';
    }

    // Credentials in the URL are a redirect/parsing-confusion vector and are
    // never needed for a webhook.
    if (parsed.username !== '' || parsed.password !== '') {
        return 'The webhook URL must not contain a username or password.';
    }

    // `URL` leaves `port` empty when it matches the scheme default, so an empty
    // string here already means 443.
    if (parsed.port !== '' && !ALLOWED_PORTS.includes(Number(parsed.port))) {
        return 'The webhook URL must use the standard HTTPS port (443).';
    }

    // `hostname` strips the brackets from an IPv6 literal but keeps a trailing
    // root dot, which would defeat the blocked-host comparison.
    const host = parsed.hostname.toLowerCase().replace(/\.+$/, '');

    if (host === '') {
        return 'The webhook URL is missing a hostname.';
    }

    if (BLOCKED_HOSTS.includes(host) || BLOCKED_SUFFIXES.some(s => host.endsWith(s))) {
        return 'That webhook host is not allowed.';
    }

    if (net.isIP(host) !== 0) {
        return isPublicIp(host) ? null : NOT_PUBLIC;
    }

    let addresses;
    try {
        // `dns.lookup`, not `resolve4`/`resolve6`: this is the same resolution
        // path the HTTP client will take, so it also sees hosts-file entries
        // that a pure DNS query would miss.
        addresses = await dns.promises.lookup(host, { all: true, verbatim: true });
    } catch {
        // Fail closed: an unresolvable host cannot be a working webhook, and
        // accepting it would let an attacker add the DNS record afterwards.
        return 'That hostname could not be resolved. Check the URL and try again.';
    }

    if (!addresses.length) {
        return 'That hostname could not be resolved. Check the URL and try again.';
    }

    // Every answer must be public — a host that returns one public and one
    // internal address is still a way in.
    for (const { address } of addresses) {
        if (!isPublicIp(address)) {
            return NOT_PUBLIC;
        }
    }

    return null;
}

/** @param {string|null|undefined} url */
export async function isSafeWebhookUrl(url) {
    return (await validateWebhookUrl(url)) === null;
}

/**
 * True only for globally-routable addresses.
 *
 * @param {string} ip
 */
export function isPublicIp(ip) {
    const version = net.isIP(ip);

    if (version === 4) {
        const bytes = parseV4(ip);
        return bytes !== null && !BLOCKED_V4.some(([base, bits]) => inPrefix(bytes, parseV4(base), bits));
    }

    if (version === 6) {
        const bytes = parseV6(ip);
        if (bytes === null) {
            return false;
        }

        // v4-mapped (::ffff:a.b.c.d) and NAT64 (64:ff9b::/96) carry a real IPv4
        // destination in their last four bytes. Judge them as that address.
        const mapped = mappedV4(bytes);
        if (mapped !== null) {
            return isPublicIp(mapped);
        }

        return !BLOCKED_V6.some(([base, bits]) => inPrefix(bytes, parseV6(base), bits));
    }

    return false;
}

/**
 * Compare the first `bits` bits of two equal-length address byte arrays.
 *
 * @param {Uint8Array|null} bytes
 * @param {Uint8Array|null} prefix
 * @param {number} bits
 */
function inPrefix(bytes, prefix, bits) {
    if (bytes === null || prefix === null || bytes.length !== prefix.length) {
        return false;
    }

    const wholeBytes = bits >> 3;

    for (let i = 0; i < wholeBytes; i++) {
        if (bytes[i] !== prefix[i]) {
            return false;
        }
    }

    const remaining = bits & 7;
    if (remaining === 0) {
        return true;
    }

    const mask = 0xff << (8 - remaining) & 0xff;

    return (bytes[wholeBytes] & mask) === (prefix[wholeBytes] & mask);
}

/**
 * @param {string} ip
 * @returns {Uint8Array|null}
 */
function parseV4(ip) {
    const parts = ip.split('.');
    if (parts.length !== 4) {
        return null;
    }

    const bytes = new Uint8Array(4);
    for (let i = 0; i < 4; i++) {
        // Reject anything but plain decimal: net.isIP already did, but this
        // function is also called on the constants above.
        if (!/^\d{1,3}$/.test(parts[i])) {
            return null;
        }
        const value = Number(parts[i]);
        if (value > 255) {
            return null;
        }
        bytes[i] = value;
    }

    return bytes;
}

/**
 * @param {string} ip
 * @returns {Uint8Array|null}
 */
function parseV6(ip) {
    let text = ip;

    // Strip a zone index (fe80::1%eth0) — it is routing information, not part
    // of the address.
    const zone = text.indexOf('%');
    if (zone !== -1) {
        text = text.slice(0, zone);
    }

    // A trailing dotted-quad (::ffff:127.0.0.1) becomes two hex groups.
    const dotted = text.match(/(\d{1,3}(?:\.\d{1,3}){3})$/);
    if (dotted) {
        const quad = parseV4(dotted[1]);
        if (quad === null) {
            return null;
        }
        const hi = (quad[0] << 8 | quad[1]).toString(16);
        const lo = (quad[2] << 8 | quad[3]).toString(16);
        text = text.slice(0, dotted.index) + hi + ':' + lo;
    }

    const halves = text.split('::');
    if (halves.length > 2) {
        return null;
    }

    const head = halves[0] ? halves[0].split(':') : [];
    const tail = halves.length === 2 ? (halves[1] ? halves[1].split(':') : []) : [];

    if (halves.length === 1 && head.length !== 8) {
        return null;
    }
    if (head.length + tail.length > 8) {
        return null;
    }

    const groups = [
        ...head,
        ...new Array(8 - head.length - tail.length).fill('0'),
        ...tail,
    ];

    const bytes = new Uint8Array(16);
    for (let i = 0; i < 8; i++) {
        if (!/^[0-9a-f]{1,4}$/i.test(groups[i])) {
            return null;
        }
        const value = parseInt(groups[i], 16);
        bytes[i * 2]     = value >> 8;
        bytes[i * 2 + 1] = value & 0xff;
    }

    return bytes;
}

/**
 * If the address embeds an IPv4 destination, return it in dotted form.
 *
 * @param {Uint8Array} bytes
 * @returns {string|null}
 */
function mappedV4(bytes) {
    const isV4Mapped = inPrefix(bytes, parseV6('::ffff:0:0'), 96);
    const isNat64    = inPrefix(bytes, parseV6('64:ff9b::'), 96);

    if (!isV4Mapped && !isNat64) {
        return null;
    }

    return `${bytes[12]}.${bytes[13]}.${bytes[14]}.${bytes[15]}`;
}
