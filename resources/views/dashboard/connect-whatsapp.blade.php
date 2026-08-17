@extends('layouts.dashboard')
@section('content')

<div class="page-header">
    <h1>📱 Connect WhatsApp Bot</h1>
    <p>Link your restaurant's WhatsApp phone number to enable AI automated ordering</p>
</div>

<div class="grid2" style="align-items: start;">
    
    <!-- QR Code Card -->
    <div class="card" style="padding: 24px; text-align: center;">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 8px;">Scan QR Code to Connect</h2>
        <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
            Open WhatsApp on your restaurant phone to scan this QR code
        </p>

        <!-- QR Display Container -->
        <div id="qr-wrapper" style="min-height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fafafa; border: 1px dashed #ddd; border-radius: 16px; padding: 20px;">
            <div id="qr-loading" style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border: 3px solid #e5e7eb; border-top-color: #0e0e10; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <span style="font-size: 13px; color: #888;">Fetching live QR code from bot...</span>
            </div>

            <img id="qr-image" src="" alt="WhatsApp QR Code" style="display: none; width: 240px; height: 240px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); background: #fff; padding: 8px;">

            <div id="qr-connected" style="display: none; flex-direction: column; align-items: center; text-align: center; gap: 8px;">
                <div style="width: 60px; height: 60px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 6px;">
                    ✓
                </div>
                <h3 style="font-size: 18px; font-weight: 700; color: #166534;">WhatsApp Connected!</h3>
                <p style="font-size: 13px; color: #4b5563;" id="connected-number">Bot is actively receiving and processing orders</p>
                <div style="margin-top: 12px;">
                    <span style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 6px 14px; border-radius: 99px; font-size: 12px; font-weight: 600;">
                        ● Status: LIVE & READY
                    </span>
                </div>
            </div>

            <div id="qr-offline" style="display: none; flex-direction: column; align-items: center; text-align: center; gap: 8px;">
                <div style="font-size: 32px; margin-bottom: 4px;">⚠️</div>
                <h3 style="font-size: 15px; font-weight: 700; color: #991b1b;">Bot Process Offline</h3>
                <p style="font-size: 12px; color: #6b7280; max-width: 260px;">
                    The bot is currently not running in the background. Start it in terminal or with PM2:
                </p>
                <code style="background: #1e293b; color: #38bdf8; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-family: monospace; margin-top: 6px;">
                    node bot/index.js
                </code>
            </div>
        </div>

        <div style="margin-top: 16px; font-size: 11px; color: #9ca3af;" id="poll-indicator">
            Auto-checking connection status every 3s...
        </div>
    </div>

    <!-- Instructions Card -->
    <div class="card" style="padding: 24px;">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">How to link your WhatsApp:</h2>
        
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 26px; height: 26px; background: #0e0e10; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">
                    1
                </div>
                <div>
                    <h4 style="font-size: 13px; font-weight: 600; color: #111;">Open WhatsApp on your Phone</h4>
                    <p style="font-size: 12px; color: #666; margin-top: 2px;">Use the official phone number registered for <strong>{{ $restaurant->name }}</strong> ({{ $restaurant->whatsapp_number }}).</p>
                </div>
            </div>

            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 26px; height: 26px; background: #0e0e10; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">
                    2
                </div>
                <div>
                    <h4 style="font-size: 13px; font-weight: 600; color: #111;">Go to Linked Devices</h4>
                    <p style="font-size: 12px; color: #666; margin-top: 2px;">
                        On Android: Tap <strong>⋮ (3 dots)</strong> &gt; <strong>Linked Devices</strong><br>
                        On iPhone: Go to <strong>Settings</strong> &gt; <strong>Linked Devices</strong>
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 26px; height: 26px; background: #0e0e10; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">
                    3
                </div>
                <div>
                    <h4 style="font-size: 13px; font-weight: 600; color: #111;">Tap "Link a Device"</h4>
                    <p style="font-size: 12px; color: #666; margin-top: 2px;">Point your camera at the QR code shown on the left. Once scanned, the bot connects automatically!</p>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <h4 style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">💡 Tips:</h4>
            <ul style="font-size: 12px; color: #64748b; padding-left: 16px; line-height: 1.5;">
                <li>Keep the restaurant phone connected to the internet.</li>
                <li>You can link or unlink devices anytime from WhatsApp settings.</li>
                <li>Session data is saved securely on your local server.</li>
            </ul>
        </div>
    </div>

</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
    let isConnected = false;

    async function fetchStatusWithTimeout(url, timeoutMs = 1000) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
        try {
            const res = await fetch(url, { signal: controller.signal });
            clearTimeout(timeoutId);
            if (res.ok) return await res.json();
        } catch (e) {
            clearTimeout(timeoutId);
        }
        return null;
    }

    async function checkBotStatus() {
        if (isConnected) return; // Stop polling once connected

        // 1. Try super-fast direct connection to bot internal port (0ms delay)
        let data = await fetchStatusWithTimeout('http://127.0.0.1:3000/qr-status', 800);

        // 2. Fallback to Laravel proxy API if direct port fails
        if (!data) {
            data = await fetchStatusWithTimeout('/api/bot/qr-status', 1500);
        }

        const loadingEl   = document.getElementById('qr-loading');
        const imageEl     = document.getElementById('qr-image');
        const connectedEl = document.getElementById('qr-connected');
        const offlineEl   = document.getElementById('qr-offline');

        if (!data || data.status === 'offline') {
            loadingEl.style.display   = 'none';
            imageEl.style.display     = 'none';
            connectedEl.style.display = 'none';
            offlineEl.style.display   = 'flex';
            return;
        }

        if (data.status === 'connected') {
            isConnected               = true;
            loadingEl.style.display   = 'none';
            imageEl.style.display     = 'none';
            offlineEl.style.display   = 'none';
            connectedEl.style.display = 'flex';
            if (data.bot_number) {
                document.getElementById('connected-number').innerText = 'Connected Number: +' + data.bot_number;
            }
        } else if (data.status === 'qr' && data.qr) {
            loadingEl.style.display   = 'none';
            connectedEl.style.display = 'none';
            offlineEl.style.display   = 'none';
            imageEl.style.display     = 'block';
            if (imageEl.src !== data.qr) {
                imageEl.src           = data.qr;
            }
        }
    }

    // Check immediately, then poll every 1.2 seconds
    checkBotStatus();
    const pollTimer = setInterval(checkBotStatus, 1200);
</script>

@endsection
