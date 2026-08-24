import http from 'http';
import { timingSafeEqual } from 'crypto';
import { sendWhatsAppText } from '../utils/WhatsAppSender.js';
import { getDbPool } from '../services/Database.js';

/**
 * Shared secret that every request must present as `X-Bot-Token`. Must match
 * BOT_INTERNAL_TOKEN in the project .env, which Laravel reads as
 * config('app.bot_internal_token').
 *
 * Read on each use rather than captured at import time: `dotenv.config()` runs
 * from module bodies elsewhere in the graph, so a module-level constant here
 * would silently be empty if the import order ever changed — and an empty token
 * stops this server from starting at all.
 */
function authToken() {
    return (process.env.BOT_INTERNAL_TOKEN || '').trim();
}

/** Loopback only. Laravel proxies the dashboard's polling through itself. */
const BIND_ADDRESS = '127.0.0.1';

/**
 * Constant-time comparison, so a wrong token cannot be recovered by timing the
 * rejection.
 */
function tokenMatches(presented) {
    const expected = authToken();

    if (!expected || typeof presented !== 'string' || presented.length === 0) {
        return false;
    }

    const a = Buffer.from(presented);
    const b = Buffer.from(expected);

    // timingSafeEqual throws when the buffers differ in length, so that case has
    // to be handled first. It only reveals the token's length, which is not
    // secret — the value is.
    if (a.length !== b.length) {
        return false;
    }

    return timingSafeEqual(a, b);
}

/**
 * InternalServer — lightweight HTTP server that:
 *  1. Lets Laravel check Bot connection status & live QR code image (GET /qr-status)
 *  2. Lets Laravel push WhatsApp messages to customers/riders (POST /send-message)
 *  3. Lets Laravel/Dashboard request a clean bot restart/re-link (POST /restart)
 *
 * Every route is privileged: the QR code is a pairing credential for the
 * restaurant's WhatsApp account, /send-message speaks as the restaurant, and
 * /restart drops the live session. It previously listened on 0.0.0.0 with
 * `Access-Control-Allow-Origin: *` and no authentication whatsoever, so anyone
 * who could reach the host could take the account over. It now binds to loopback
 * and requires a shared secret.
 */
export class InternalServer {
    constructor(client, port = 3000) {
        this.client            = client;
        this.port              = parseInt(port);
        this.status            = 'initializing'; // initializing | qr | connected | disconnected
        this.qrDataUrl         = null;
        this.botNumber         = null;
        this.onRestartCallback = null;
        this.restaurantId      = null;
        this.restaurantService = null;
    }

    setRestaurantId(id) {
        this.restaurantId = id;
    }

    setRestaurantService(service) {
        this.restaurantService = service;
    }

    async _syncBotStatusToDb(status) {
        try {
            const db = getDbPool();
            if (this.restaurantId) {
                await db.query(
                    `UPDATE restaurants SET bot_status = ?, bot_last_seen_at = ? WHERE id = ?`,
                    [status, new Date(), this.restaurantId]
                );
            } else {
                // Update all restaurants associated to this bot number
                if (this.botNumber) {
                    await db.query(
                        `UPDATE restaurants SET bot_status = ?, bot_last_seen_at = ? WHERE whatsapp_number = ?`,
                        [status, new Date(), this.botNumber]
                    );
                }
            }
        } catch (e) {
            console.debug('Bot status DB sync:', e.message);
        }
    }

    /**
     * Only the rendered image is kept. The raw QR string is a pairing credential
     * and was previously both stored and served as `qr_raw`, which nothing used.
     */
    setQr(qrDataUrl) {
        this.status    = 'qr_pending';
        this.qrDataUrl = qrDataUrl;
        this._syncBotStatusToDb('qr_pending');
    }

    setReady(botNumber) {
        this.status    = 'connected';
        this.qrDataUrl = null;
        this.botNumber = botNumber;
        this._syncBotStatusToDb('connected');
    }

    setDisconnected(reason) {
        this.status = 'disconnected';
        // Sync to the DB BEFORE clearing botNumber. Otherwise the UPDATE would
        // have no row to match (botNumber null, and restaurantId possibly null),
        // so the dashboard would keep showing the last status forever.
        // restaurantId (pinned on ready) is the stable primary target and is
        // intentionally NOT cleared here.
        this._syncBotStatusToDb('disconnected');
        this.botNumber = null;
    }

    setClient(client) {
        this.client = client;
    }

    setRestartHandler(cb) {
        this.onRestartCallback = cb;
    }

    async _logErrorToDb(errorMessage) {
        try {
            const db = getDbPool();
            if (this.restaurantId) {
                await db.query(
                    `UPDATE restaurants SET last_error = ?, last_error_at = ? WHERE id = ?`,
                    [String(errorMessage).substring(0, 500), new Date(), this.restaurantId]
                );
            } else if (this.botNumber) {
                await db.query(
                    `UPDATE restaurants SET last_error = ?, last_error_at = ? WHERE whatsapp_number = ?`,
                    [String(errorMessage).substring(0, 500), new Date(), this.botNumber]
                );
            }
        } catch (e) {
            console.debug('Error DB log:', e.message);
        }
    }

    start() {
        if (!authToken()) {
            console.error('');
            console.error('❌ BOT_INTERNAL_TOKEN is not set — refusing to start the internal control API.');
            console.error('   This server can hand out the WhatsApp pairing QR and send messages as the');
            console.error('   restaurant, so it must not run unauthenticated.');
            console.error('');
            console.error('   Add the same value to .env for both sides, e.g.:');
            console.error('     BOT_INTERNAL_TOKEN=<64 hex chars>');
            console.error('   Generate one with:  node -e "console.log(require(\'crypto\').randomBytes(32).toString(\'hex\'))"');
            console.error('');
            console.error('   The bot will keep taking WhatsApp orders; only the dashboard QR/status page');
            console.error('   and outgoing dashboard notifications are unavailable until this is set.');
            console.error('');
            return;
        }

        const server = http.createServer(async (req, res) => {
            // No CORS headers on purpose. Nothing in a browser talks to this
            // server any more — the dashboard polls Laravel, which proxies here
            // server-side. `Access-Control-Allow-Origin: *` would re-open exactly
            // the hole this lockdown closes.

            if (!tokenMatches(req.headers['x-bot-token'])) {
                // Deliberately terse: no hint about which part was wrong, and no
                // status/QR data in the body.
                console.warn(`🔒 Rejected unauthenticated ${req.method} ${req.url} from ${req.socket.remoteAddress}`);
                res.writeHead(401, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, error: 'Unauthorized' }));
                return;
            }

            // ── Live QR & Status Endpoint ──────────────────────────────────────
            if (req.method === 'GET' && (req.url === '/qr-status' || req.url === '/status')) {
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success:   true,
                    status:    this.status,
                    // Rendered image only. The raw QR payload used to be returned
                    // as `qr_raw` as well; nothing consumed it, and it is a
                    // pairing credential, so it is no longer exposed.
                    qr:        this.qrDataUrl,
                    bot_number:this.botNumber,
                    // Lets Laravel refuse to show this QR to a restaurant that
                    // does not own the paired account.
                    restaurant_id: this.restaurantId,
                    timestamp: new Date().toISOString(),
                }));
                return;
            }

            // ── Push WhatsApp Message ──────────────────────────────────────────
            if (req.method === 'POST' && req.url === '/send-message') {
                let body = '';
                req.on('data', chunk => { body += chunk.toString(); });
                req.on('end', async () => {
                    try {
                        const { to, message } = JSON.parse(body);

                        if (!to || !message) {
                            res.writeHead(400, { 'Content-Type': 'application/json' });
                            res.end(JSON.stringify({ success: false, error: 'Missing "to" or "message"' }));
                            return;
                        }

                        if (!this.client) {
                            res.writeHead(503, { 'Content-Type': 'application/json' });
                            res.end(JSON.stringify({ success: false, error: 'WhatsApp client is not ready' }));
                            return;
                        }

                        const sent = await sendWhatsAppText(this.client, to, message);
                        if (sent) {
                            res.writeHead(200, { 'Content-Type': 'application/json' });
                            res.end(JSON.stringify({ success: true }));
                        } else {
                            const errMsg = `Failed to send WhatsApp message to ${to}`;
                            console.error('❌', errMsg);
                            this._logErrorToDb(errMsg);
                            res.writeHead(500, { 'Content-Type': 'application/json' });
                            res.end(JSON.stringify({ success: false, error: errMsg }));
                        }

                    } catch (err) {
                        console.error('❌ Internal server send error:', err.message);
                        this._logErrorToDb(err.message);
                        res.writeHead(500, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: false, error: err.message }));
                    }
                });
                return;
            }

            // ── Invalidate Menu / Restaurant Cache ─────────────────────────────
            if (req.method === 'POST' && req.url === '/invalidate-cache') {
                if (this.restaurantService) {
                    this.restaurantService.cache.clear();
                    console.log('🔄 Bot restaurant/menu cache invalidated upon dashboard update');
                }
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, message: 'Cache invalidated' }));
                return;
            }

            // ── Restart / Re-link Bot (New QR Code) ────────────────────────────
            if (req.method === 'POST' && (req.url === '/restart' || req.url === '/reset-qr')) {
                this.status    = 'initializing';
                this.qrDataUrl = null;
                this.botNumber = null;

                if (typeof this.onRestartCallback === 'function') {
                    this.onRestartCallback();
                }

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, message: 'Bot restart initiated. Generating fresh QR...' }));
                return;
            }

            res.writeHead(404);
            res.end('Not found');
        });

        server.on('error', (err) => {
            if (err.code === 'EADDRINUSE') {
                console.warn(`⚠️  Port ${this.port} is already in use. Internal push API server skipped.`);
            } else {
                console.error(`❌ Internal server error:`, err.message);
            }
        });

        server.listen(this.port, BIND_ADDRESS, () => {
            // The old log claimed 127.0.0.1 while actually listening on 0.0.0.0.
            console.log(`🌐 Internal API listening on http://${BIND_ADDRESS}:${this.port} (token required)`);
        });
    }
}
