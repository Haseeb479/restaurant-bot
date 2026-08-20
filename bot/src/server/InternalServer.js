import http from 'http';
import { sendWhatsAppText } from '../utils/WhatsAppSender.js';
import { getDbPool } from '../services/Database.js';

/**
 * InternalServer — lightweight HTTP server that:
 *  1. Lets Laravel check Bot connection status & live QR code image (GET /qr-status)
 *  2. Lets Laravel push WhatsApp messages to customers/riders (POST /send-message)
 *  3. Lets Laravel/Dashboard request a clean bot restart/re-link (POST /restart)
 */
export class InternalServer {
    constructor(client, port = 3000) {
        this.client            = client;
        this.port              = parseInt(port);
        this.status            = 'initializing'; // initializing | qr | connected | disconnected
        this.qrDataUrl         = null;
        this.qrRaw             = null;
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

    setQr(qrRaw, qrDataUrl) {
        this.status    = 'qr_pending';
        this.qrRaw     = qrRaw;
        this.qrDataUrl = qrDataUrl;
        this._syncBotStatusToDb('qr_pending');
    }

    setReady(botNumber) {
        this.status    = 'connected';
        this.qrDataUrl = null;
        this.qrRaw     = null;
        this.botNumber = botNumber;
        this._syncBotStatusToDb('connected');
    }

    setDisconnected(reason) {
        this.status    = 'disconnected';
        this.botNumber = null;
        this._syncBotStatusToDb('disconnected');
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
        const server = http.createServer(async (req, res) => {
            // Enable CORS for dashboard/web polling
            res.setHeader('Access-Control-Allow-Origin', '*');
            res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

            if (req.method === 'OPTIONS') {
                res.writeHead(204);
                res.end();
                return;
            }

            // ── Live QR & Status Endpoint ──────────────────────────────────────
            if (req.method === 'GET' && (req.url === '/qr-status' || req.url === '/status')) {
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success:   true,
                    status:    this.status,
                    qr:        this.qrDataUrl,
                    qr_raw:    this.qrRaw,
                    bot_number:this.botNumber,
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
                this.qrRaw     = null;
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

        server.listen(this.port, '0.0.0.0', () => {
            console.log(`🌐 Internal API running on http://127.0.0.1:${this.port}`);
        });
    }
}
