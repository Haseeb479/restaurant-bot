@extends('layouts.dashboard')
@section('title', 'WhatsApp Bot')
@section('header_title', 'WhatsApp Bot Connection')
@section('header_subtitle', 'Link your restaurant phone to enable automated WhatsApp customer ordering')

@section('content')

<style>
    .connect-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 24px;
        align-items: start;
    }

    .card-panel {
        background: #ffffff;
        border: 1px solid var(--border-color, #e2e8f0);
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
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }

    .card-panel-header p {
        font-size: 13px;
        color: #64748b;
        margin-top: 2px;
    }

    .tabs-nav {
        display: flex;
        gap: 8px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        margin-bottom: 20px;
    }

    .tab-btn {
        flex: 1;
        padding: 9px 14px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 9px;
        border: none;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .tab-btn.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .pairing-box {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .code-display {
        background: #0f172a;
        color: #38bdf8;
        font-family: ui-monospace, monospace;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: 4px;
        padding: 14px 24px;
        border-radius: 12px;
        margin: 14px 0;
        display: inline-block;
        box-shadow: 0 4px 12px rgba(15,23,42,0.15);
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
        .connect-grid { grid-template-columns: 1fr; gap: 16px; }
        .card-panel { padding: 18px 14px; border-radius: 16px; }
        .pairing-box { padding: 20px 14px; min-height: 240px; }
    }
</style>

<div class="connect-grid">
    <!-- LEFT: CONNECTION INTERFACE -->
    <div class="card-panel">
        <div class="card-panel-header">
            <h3>🤖 Link Your Restaurant's WhatsApp</h3>
            <p>Connect WhatsApp number <strong>{{ $restaurant->whatsapp_number }}</strong> to take customer orders</p>
        </div>

        <!-- TABS: QR SCAN vs PAIRING CODE -->
        <div class="tabs-nav">
            <button type="button" class="tab-btn active" id="tab-qr-btn" onclick="switchTab('qr')">
                📷 Scan QR Code (Fastest)
            </button>
            <button type="button" class="tab-btn" id="tab-pairing-btn" onclick="switchTab('pairing')">
                🔢 8-Digit Pairing Code
            </button>
        </div>

        <!-- 1. QR CODE SECTION (Default) -->
        <div id="section-qr" class="pairing-box" style="display: flex;">
            <div id="qr-loading" style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #4f46e5; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <span style="font-size: 13px; color: #64748b; font-weight: 600;">Fetching live WhatsApp QR code...</span>
            </div>

            <img id="qr-image" src="" alt="WhatsApp QR Code" style="display: none; width: 250px; height: 250px; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); background: #fff; padding: 10px;">
        </div>

        <!-- 2. PAIRING CODE SECTION -->
        <div id="section-pairing" class="pairing-box" style="display: none;">
            <div id="pairing-initial" style="width: 100%; max-width: 360px;">
                <p style="font-size: 13px; color: #475569; margin-bottom: 14px;">
                    Link without camera scan. Enter your WhatsApp number to receive an 8-digit code:
                </p>
                <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <input type="text" id="pairing-phone" value="{{ $restaurant->whatsapp_number }}" placeholder="923001234567"
                           style="flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; font-weight: 600;">
                    <button type="button" onclick="generatePairingCode()" class="btn btn-primary" style="padding: 10px 16px; white-space: nowrap;">
                        ⚡ Get Code
                    </button>
                </div>
                <p style="font-size: 11px; color: #94a3b8;">Format: 923001234567 or 03001234567</p>
            </div>

            <!-- Pairing Code Result Display -->
            <div id="pairing-result" style="display: none; flex-direction: column; align-items: center; width: 100%;">
                <span style="font-size: 12px; font-weight: 700; color: #0284c7; text-transform: uppercase;">Your 8-Digit WhatsApp Code:</span>
                <div class="code-display" id="pairing-code-text">---- ----</div>
                <p style="font-size: 12px; color: #475569; max-width: 320px; line-height: 1.4;">
                    On your phone, open <strong>WhatsApp → Linked Devices → Link with phone number instead</strong> and type the code above.
                </p>
                <button type="button" onclick="generatePairingCode()" class="btn btn-secondary" style="margin-top: 14px; font-size: 12px;">
                    🔄 Generate New Code
                </button>
            </div>

            <!-- Connected State Display -->
            <div id="pairing-connected" style="display: none; flex-direction: column; align-items: center; text-align: center;">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; font-size: 26px; color: #16a34a;">
                    ✓
                </div>
                <h3 style="font-size: 18px; font-weight: 800; color: #166534;">WhatsApp Bot is Connected!</h3>
                <p style="font-size: 13px; color: #475569;" id="connected-number">Active on {{ $restaurant->whatsapp_number }}</p>
                <div style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    <span class="badge-status delivered" style="font-size: 12px; padding: 5px 12px;">
                        ● Status: LIVE & READY
                    </span>
                    <button type="button" onclick="requestRestart()" class="btn btn-secondary" style="font-size: 12px;">
                        🔄 Re-link / Reconnect
                    </button>
                </div>
            </div>
        </div>

        <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 11px; color: #94a3b8;" id="poll-indicator">
                ● Auto-syncing connection status...
            </div>
            <button type="button" onclick="requestRestart()" style="background: none; border: none; font-size: 12px; color: #4f46e5; font-weight: 700; cursor: pointer; text-decoration: underline;">
                🔄 Reset / Re-pair Instance
            </button>
        </div>
    </div>

    <!-- RIGHT: INSTRUCTIONS CARD -->
    <div class="card-panel">
        <div class="card-panel-header">
            <h3>How to Connect in 30 Seconds</h3>
            <p>Simple 3-step pairing guide</p>
        </div>

        <div class="step-list">
            <div class="step-item">
                <div class="step-num">1</div>
                <div class="step-text">
                    <h4>Open WhatsApp on your Phone</h4>
                    <p>Use the WhatsApp app on <strong>{{ $restaurant->whatsapp_number }}</strong>.</p>
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
                    <h4>Tap "Link a Device"</h4>
                    <p>
                        Choose <strong>"Link with phone number instead"</strong> and enter the 8-digit code, or point camera at the QR code.
                    </p>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; padding: 14px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;">
            <strong style="font-size: 12px; color: #166534;">🔒 Dedicated Restaurant Bot:</strong>
            <p style="font-size: 11px; color: #15803d; margin-top: 2px;">
                Your bot runs on its own isolated WhatsApp instance. Customers text your number directly and never mix with other restaurants.
            </p>
        </div>
    </div>
</div>

<script>
    const STATUS_URL       = @json(route('dashboard.bot-status', $restaurant->id, false));
    const PAIRING_CODE_URL = @json(route('dashboard.bot-pairing-code', $restaurant->id, false));
    const QR_URL           = @json(route('dashboard.bot-qr', $restaurant->id, false));
    const RESTART_URL      = @json(route('dashboard.bot-restart', $restaurant->id, false));
    const CSRF_TOKEN       = @json(csrf_token());

    let currentTab = 'qr';
    let pollInterval = null;
    let qrPollInterval = null;

    function switchTab(tab) {
        currentTab = tab;
        document.getElementById('tab-qr-btn').classList.toggle('active', tab === 'qr');
        document.getElementById('tab-pairing-btn').classList.toggle('active', tab === 'pairing');

        document.getElementById('section-qr').style.display = tab === 'qr' ? 'flex' : 'none';
        document.getElementById('section-pairing').style.display = tab === 'pairing' ? 'flex' : 'none';

        if (tab === 'qr') {
            fetchQrCode();
        }
    }

    async function generatePairingCode() {
        const phone = document.getElementById('pairing-phone').value.trim();
        if (!phone) {
            alert('Please enter your WhatsApp number.');
            return;
        }

        document.getElementById('pairing-initial').style.display = 'none';
        document.getElementById('pairing-result').style.display = 'flex';
        document.getElementById('pairing-code-text').innerText = 'GENERATING...';

        try {
            const res = await fetch(PAIRING_CODE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone: phone })
            });

            const data = await res.json().catch(() => ({}));

            if (data.success && data.pairing_code) {
                const raw = data.pairing_code;
                const formatted = raw.length === 8 ? raw.slice(0, 4) + ' - ' + raw.slice(4) : raw;
                document.getElementById('pairing-code-text').innerText = formatted;
            } else {
                document.getElementById('pairing-code-text').innerText = 'ERROR';
                alert(data.message || 'Could not generate pairing code. Please try scanning the QR code instead.');
            }
        } catch (err) {
            document.getElementById('pairing-code-text').innerText = 'ERROR';
            alert('Could not reach server: ' + (err.message || 'Please check connection'));
        }
    }

    async function fetchQrCode() {
        try {
            const res = await fetch(QR_URL, { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));

            if (data.success && data.qr) {
                const img = document.getElementById('qr-image');
                img.src = data.qr;
                img.style.display = 'block';
                const loading = document.getElementById('qr-loading');
                if (loading) loading.style.display = 'none';

                // Stop rapid polling once QR is loaded; refresh every 20s
                if (qrPollInterval) {
                    clearInterval(qrPollInterval);
                    qrPollInterval = setInterval(fetchQrCode, 20000);
                }
            }
        } catch (e) {
            console.warn('QR fetch retry:', e);
        }
    }

    async function checkBotStatus() {
        try {
            const res  = await fetch(STATUS_URL, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));

            if (data.status === 'connected' || data.is_open) {
                if (qrPollInterval) {
                    clearInterval(qrPollInterval);
                    qrPollInterval = null;
                }
                const tabsNav = document.querySelector('.tabs-nav');
                if (tabsNav) tabsNav.style.display = 'none';

                const secQr = document.getElementById('section-qr');
                const secPairing = document.getElementById('section-pairing');
                if (secQr) secQr.style.display = 'none';
                if (secPairing) secPairing.style.display = 'flex';

                document.getElementById('pairing-initial').style.display = 'none';
                document.getElementById('pairing-result').style.display = 'none';
                document.getElementById('pairing-connected').style.display = 'flex';
                const ind = document.getElementById('poll-indicator');
                if (ind) {
                    ind.innerText = '✓ WhatsApp instance LIVE & connected';
                    ind.style.color = '#16a34a';
                    ind.style.fontWeight = '700';
                }
            }
        } catch (err) {}
    }

    async function requestRestart() {
        if (!confirm('Are you sure you want to reset this WhatsApp instance to re-pair?')) return;

        try {
            await fetch(RESTART_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                }
            });
            location.reload();
        } catch (e) {
            location.reload();
        }
    }

    // Initial checks and polling
    checkBotStatus();
    pollInterval = setInterval(checkBotStatus, 5000);

    fetchQrCode();
    qrPollInterval = setInterval(fetchQrCode, 2500);

    window.addEventListener('beforeunload', () => {
        if (pollInterval) clearInterval(pollInterval);
        if (qrPollInterval) clearInterval(qrPollInterval);
    });
</script>

@endsection
