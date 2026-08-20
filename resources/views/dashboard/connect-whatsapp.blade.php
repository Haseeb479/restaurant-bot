@extends('layouts.dashboard')
@section('title', 'WhatsApp Bot')
@section('header_title', 'WhatsApp Bot Connection')
@section('header_subtitle', 'Link your restaurant phone to enable automated WhatsApp customer ordering')

@section('content')

<style>
    .qr-grid {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 20px;
        align-items: start;
    }

    .card-panel {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .card-panel-header {
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .card-panel-header h3 {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .card-panel-header p {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }

    .qr-box {
        min-height: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 24px;
        text-align: center;
    }

    .step-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .step-item {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .step-num {
        width: 28px;
        height: 28px;
        background: #0f172a;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        flex-shrink: 0;
    }

    .step-text h4 {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }

    .step-text p {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.4;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 900px) {
        .qr-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="qr-grid">
    <!-- QR SCANNER CARD -->
    <div class="card-panel">
        <div class="card-panel-header">
            <h3>📱 Scan QR Code with WhatsApp</h3>
            <p>Open WhatsApp on <strong>{{ $restaurant->whatsapp_number }}</strong> to link your bot</p>
        </div>

        <div class="qr-box" id="qr-wrapper">
            <!-- Loading state -->
            <div id="qr-loading" style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #4f46e5; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <span style="font-size: 13px; color: #64748b; font-weight: 600;">Fetching live QR code from bot engine...</span>
            </div>

            <!-- QR image -->
            <img id="qr-image" src="" alt="WhatsApp QR Code" style="display: none; width: 240px; height: 240px; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); background: #fff; padding: 10px;">

            <!-- Connected state -->
            <div id="qr-connected" style="display: none; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                <div style="width: 60px; height: 60px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px;">
                    ✓
                </div>
                <h3 style="font-size: 18px; font-weight: 800; color: #166534;">WhatsApp Bot is Connected!</h3>
                <p style="font-size: 13px; color: #475569;" id="connected-number">Active on {{ $restaurant->whatsapp_number }}</p>
                <div style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    <span class="badge-status delivered" style="font-size: 12px; padding: 5px 12px;">
                        ● Status: LIVE & READY
                    </span>
                    <button type="button" onclick="requestNewQr()" class="btn btn-secondary" style="font-size: 12px;">
                        🔄 Re-link / New QR
                    </button>
                </div>
            </div>

            <!-- Offline state -->
            <div id="qr-offline" style="display: none; flex-direction: column; align-items: center; text-align: center; gap: 10px;">
                <div style="font-size: 32px;">⚠️</div>
                <h3 style="font-size: 15px; font-weight: 800; color: #991b1b;">Bot Background Process Offline</h3>
                <p style="font-size: 12px; color: #64748b; max-width: 280px; line-height: 1.4;">
                    The Node.js WhatsApp engine is starting. Start it in terminal or with PM2:
                </p>
                <code style="background: #0f172a; color: #38bdf8; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-family: monospace;">
                    node bot/index.js
                </code>
            </div>
        </div>

        <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 11px; color: #94a3b8;" id="poll-indicator">
                ● Auto-syncing connection status...
            </div>
            <button type="button" onclick="requestNewQr()" style="background: none; border: none; font-size: 12px; color: #4f46e5; font-weight: 700; cursor: pointer; text-decoration: underline;">
                🔄 Reset / Refresh QR Code
            </button>
        </div>
    </div>

    <!-- INSTRUCTIONS CARD -->
    <div class="card-panel">
        <div class="card-panel-header">
            <h3>How to Connect in 30 Seconds</h3>
            <p>Simple 3-step guide to connect your WhatsApp number</p>
        </div>

        <div class="step-list">
            <div class="step-item">
                <div class="step-num">1</div>
                <div class="step-text">
                    <h4>Open WhatsApp on your Phone</h4>
                    <p>Use the WhatsApp app on the phone with number <strong>{{ $restaurant->whatsapp_number }}</strong>.</p>
                </div>
            </div>

            <div class="step-item">
                <div class="step-num">2</div>
                <div class="step-text">
                    <h4>Go to Linked Devices</h4>
                    <p>
                        <strong>Android:</strong> Tap ⋮ (3 dots top right) → <strong>Linked Devices</strong><br>
                        <strong>iPhone:</strong> Go to <strong>Settings</strong> → <strong>Linked Devices</strong>
                    </p>
                </div>
            </div>

            <div class="step-item">
                <div class="step-num">3</div>
                <div class="step-text">
                    <h4>Tap "Link a Device" and Scan</h4>
                    <p>Point your camera at the QR code on the left. Once scanned, your bot connects instantly and starts taking customer orders.</p>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; padding: 14px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;">
            <strong style="font-size: 12px; color: #166534;">💡 Multi-Device Support:</strong>
            <p style="font-size: 11px; color: #15803d; margin-top: 2px;">
                You can still use WhatsApp on your phone normally while the bot handles orders in the background.
            </p>
        </div>
    </div>
</div>

<script>
    const BOT_API = 'http://' + window.location.hostname + ':3000';
    let pollInterval = null;

    async function checkBotStatus() {
        try {
            const res = await fetch(BOT_API + '/qr-status', { cache: 'no-store' });
            if (!res.ok) throw new Error('Status ' + res.status);
            const data = await res.json();

            document.getElementById('qr-loading').style.display = 'none';

            if (data.status === 'connected') {
                document.getElementById('qr-image').style.display = 'none';
                document.getElementById('qr-offline').style.display = 'none';
                document.getElementById('qr-connected').style.display = 'flex';
                if (data.bot_number) {
                    document.getElementById('connected-number').innerText = 'Connected Phone: +' + data.bot_number;
                }
                document.getElementById('poll-indicator').innerText = '✓ Live connection active';
            } else if (data.qr) {
                document.getElementById('qr-connected').style.display = 'none';
                document.getElementById('qr-offline').style.display = 'none';
                const img = document.getElementById('qr-image');
                img.src = data.qr;
                img.style.display = 'block';
                document.getElementById('poll-indicator').innerText = '● QR Code ready — scan now';
            } else {
                document.getElementById('qr-image').style.display = 'none';
                document.getElementById('qr-connected').style.display = 'none';
                document.getElementById('qr-offline').style.display = 'none';
                document.getElementById('qr-loading').style.display = 'flex';
                document.getElementById('poll-indicator').innerText = '● Initializing WhatsApp session...';
            }
        } catch (err) {
            document.getElementById('qr-loading').style.display = 'none';
            document.getElementById('qr-image').style.display = 'none';
            document.getElementById('qr-connected').style.display = 'none';
            document.getElementById('qr-offline').style.display = 'flex';
            document.getElementById('poll-indicator').innerText = '⚠️ Internal API unreachable (Port 3000)';
        }
    }

    async function requestNewQr() {
        document.getElementById('qr-connected').style.display = 'none';
        document.getElementById('qr-image').style.display = 'none';
        document.getElementById('qr-offline').style.display = 'none';
        document.getElementById('qr-loading').style.display = 'flex';
        document.getElementById('poll-indicator').innerText = 'Generating fresh QR code...';

        try {
            await fetch(BOT_API + '/restart', { method: 'POST' });
        } catch (e) {}

        setTimeout(checkBotStatus, 2000);
    }

    // Initial check and start polling
    checkBotStatus();
    pollInterval = setInterval(checkBotStatus, 4000);

    window.addEventListener('beforeunload', () => {
        if (pollInterval) clearInterval(pollInterval);
    });
</script>

@endsection
