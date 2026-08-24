import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import dns from 'dns';

import { validateWebhookUrl, isPublicIp, isSafeWebhookUrl } from '../src/utils/WebhookUrlValidator.js';

/**
 * The webhook URL is owner-supplied (dashboard Settings) or operator-supplied
 * (GOOGLE_SHEET_WEBHOOK), and the bot POSTs customer PII to it from inside the
 * host — so every case below is a way into the private network if it regresses.
 *
 * Keep this in step with tests for App\Support\WebhookUrlValidator: the same
 * stored value is used by both processes.
 */

// One lookup up front so the DNS-dependent case can be skipped offline instead
// of failing. Everything else here is decided without touching the network.
let online = true;
try {
    await dns.promises.lookup('script.google.com');
} catch {
    online = false;
}

describe('isPublicIp', () => {
    const publicAddresses = [
        '8.8.8.8',
        '1.1.1.1',
        '203.0.113.9',
        '172.32.0.1',        // just past the 172.16/12 private block
        '100.128.0.1',       // just past the 100.64/10 CGNAT block
        '2606:4700::1111',
        '::ffff:8.8.8.8',    // v4-mapped, public payload
        '64:ff9b::8.8.8.8',  // NAT64, public payload
    ];

    const privateAddresses = [
        '0.0.0.0',
        '10.1.2.3',
        '127.0.0.1',
        '127.1.1.1',           // all of 127/8, not just .0.1
        '169.254.169.254',     // cloud metadata
        '172.16.0.1',
        '172.31.255.255',
        '192.168.1.1',
        '100.64.0.1',          // carrier-grade NAT
        '100.127.255.255',
        '192.0.0.1',
        '198.18.0.5',
        '224.0.0.1',           // multicast
        '240.0.0.1',
        '255.255.255.255',
        '::',
        '::1',
        'fc00::1',
        'fd12:3456::1',
        'fe80::1',
        'fe80::1%eth0',        // zone index must not defeat the prefix match
        'ff02::1',
        '::ffff:127.0.0.1',    // v4-mapped loopback
        '64:ff9b::127.0.0.1',  // NAT64 loopback
        'not-an-ip',
    ];

    for (const ip of publicAddresses) {
        test(`allows ${ip}`, () => assert.equal(isPublicIp(ip), true));
    }

    for (const ip of privateAddresses) {
        test(`rejects ${ip}`, () => assert.equal(isPublicIp(ip), false));
    }
});

describe('validateWebhookUrl', () => {
    const rejected = {
        'plain http':               'http://example.com/hook',
        'credentials in the URL':   'https://user:pw@example.com/hook',
        'a non-standard port':      'https://example.com:8443/hook',
        'localhost':                'https://localhost/hook',
        'a .localhost subdomain':   'https://foo.localhost/hook',
        'an mDNS .local name':      'https://printer.local/hook',
        'an .internal name':        'https://svc.internal/hook',
        'GCE metadata':             'https://metadata.google.internal/computeMetadata/',
        'case and a trailing dot':  'https://LOCALHOST./hook',
        "the bot's own API":        'https://127.0.0.1:3000/restart',
        'an IPv6 loopback literal': 'https://[::1]/hook',
        'EC2 metadata':             'https://169.254.169.254/latest/meta-data/',
        'a LAN address':            'https://192.168.0.10/hook',
        'a malformed URL':          'not a url',
        'a non-HTTP scheme':        'ftp://example.com/hook',
        'an absurdly long URL':     `https://${'a'.repeat(2100)}.com/`,
    };

    for (const [why, url] of Object.entries(rejected)) {
        test(`rejects ${why}`, async () => {
            const reason = await validateWebhookUrl(url);
            assert.equal(typeof reason, 'string', `expected a rejection reason for ${url}`);
            assert.ok(reason.length > 0);
        });
    }

    test('fails closed on a hostname that does not resolve', async () => {
        // Accepting an unresolvable host would let an attacker add the DNS
        // record afterwards.
        const reason = await validateWebhookUrl('https://this-host-does-not-exist-8f3a2b.example/hook');
        assert.match(reason, /could not be resolved/);
    });

    test('treats an empty value as "not configured", not unsafe', async () => {
        assert.equal(await validateWebhookUrl(''), null);
        assert.equal(await validateWebhookUrl('   '), null);
        assert.equal(await validateWebhookUrl(null), null);
        assert.equal(await validateWebhookUrl(undefined), null);
    });

    test('allows a public IP literal', async () => {
        assert.equal(await validateWebhookUrl('https://8.8.8.8/hook'), null);
    });

    test('allows an explicit :443', async () => {
        assert.equal(await validateWebhookUrl('https://8.8.8.8:443/hook'), null);
    });

    test('allows a real Google Apps Script endpoint', { skip: online ? false : 'no DNS available' }, async () => {
        assert.equal(await validateWebhookUrl('https://script.google.com/macros/s/AKfycbxxxx/exec'), null);
    });

    test('isSafeWebhookUrl mirrors validate()', async () => {
        assert.equal(await isSafeWebhookUrl('https://8.8.8.8/hook'), true);
        assert.equal(await isSafeWebhookUrl('https://127.0.0.1/hook'), false);
    });
});
