@extends('layouts.admin')
@section('title', 'Pending Restaurant Approvals')
@section('header_title', 'Pending Restaurant Approvals')
@section('header_subtitle', 'Review and approve/reject self-registered restaurants')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Approval Queue ({{ $pendingRestaurants->count() }})</h3>
            <p>Restaurants that have submitted registration & payment and require Super Admin verification</p>
        </div>
        <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary">← Back to All Restaurants</a>
    </div>

    @if($pendingRestaurants->isEmpty())
        <div style="text-align: center; padding: 50px 20px; color: var(--text-secondary);">
            <div style="font-size: 40px; margin-bottom: 12px;">🎉</div>
            <h4 style="font-size: 16px; font-weight: 700; color: var(--text-primary);">All Caught Up!</h4>
            <p style="font-size: 12px; margin-top: 4px;">There are currently no pending restaurant registrations requiring verification.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Restaurant & Owner</th>
                        <th>WhatsApp Bot SIM</th>
                        <th>Plan & Payment</th>
                        <th>City / Address</th>
                        <th>Submitted At</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRestaurants as $p)
                        @php
                            $lastPayment = $p->payments->first();
                        @endphp
                        <tr>
                            <td>
                                <strong style="font-size: 14px; color: var(--text-primary);">{{ $p->name }}</strong>
                                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                    Owner: <strong>{{ $p->owner_name ?: 'N/A' }}</strong> ({{ $p->owner_phone }})
                                </div>
                                @if($p->email)
                                    <div style="font-size: 11px; color: var(--text-secondary);">✉️ {{ $p->email }}</div>
                                @endif
                            </td>
                            <td>
                                <code>{{ $p->whatsapp_number }}</code>
                                <div style="font-size: 10px; color: var(--text-secondary); margin-top: 2px;">Bot Number</div>
                            </td>
                            <td>
                                <span class="badge badge-info" style="font-weight: 700;">
                                    {{ $p->subscriptionPlan?->name ?? ucfirst($p->plan) }}
                                </span>
                                <div style="margin-top: 4px;">
                                    @if($p->payment_status === 'completed')
                                        <span class="badge badge-success" style="font-size: 10px;">✓ Paid {{ $lastPayment ? ('(Rs. ' . number_format($lastPayment->amount, 0) . ' via ' . ucfirst($lastPayment->payment_method) . ')') : '' }}</span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 10px;">Pending Payment</span>
                                    @endif
                                </div>
                                @if($lastPayment && $lastPayment->payment_reference)
                                    <div style="font-size: 10px; color: var(--text-secondary); font-family: monospace; margin-top: 2px;">
                                        Ref: {{ $lastPayment->payment_reference }}
                                    </div>
                                @endif
                                @if($lastPayment && $lastPayment->payment_slip)
                                    <div style="margin-top: 5px;">
                                        <a href="{{ asset($lastPayment->payment_slip) }}" target="_blank" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 10.5px; font-weight: 700; color: #047857; border-color: #a7f3d0; background: #ecfdf5; display: inline-flex; align-items: center; gap: 4px;">
                                            <span>📄</span> View Payment Slip ↗
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div><strong>{{ $p->city ?: 'Pakistan' }}</strong></div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ Str::limit($p->address, 35) ?: 'No address specified' }}</div>
                            </td>
                            <td>
                                <div>{{ $p->created_at->format('d M Y, h:i A') }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ $p->created_at->diffForHumans() }}</div>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <button type="button" class="btn btn-success btn-sm" onclick="openApproveModal('{{ $p->id }}', '{{ addslashes($p->name) }}', '{{ addslashes($p->owner_name ?: $p->owner_phone) }}', '{{ addslashes($p->city ?: 'Pakistan') }}', '{{ $p->plan }}')">
                                        Approve & Activate ✓
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="rejectPrompt('{{ $p->id }}', '{{ addslashes($p->name) }}')">
                                        Reject ✕
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Modal for Rejection Reason -->
<div id="rejectModal" style="display:none; position:fixed; inset:0; background:rgba(11, 15, 25, 0.7); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); border-radius:16px; padding:24px; width:460px; max-width:90%; border:1px solid var(--border-card); box-shadow:var(--shadow-elevated);">
        <h3 style="margin-bottom:6px; font-size:17px; font-weight:800; color:var(--text-heading);">Reject Application</h3>
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:16px;">Specify the reason for rejection for <strong id="rejectRestName" style="color:var(--text-heading);"></strong>.</p>
        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Rejection Reason</label>
                <textarea name="reason" class="form-textarea" rows="3" required placeholder="e.g. Unverified phone number, duplicate restaurant branch..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Approval & Dedicated AI Key Assignment -->
<div id="approveModal" style="display:none; position:fixed; inset:0; background:rgba(11, 15, 25, 0.7); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); border-radius:18px; padding:26px; width:520px; max-width:92%; border:1px solid var(--border-card); box-shadow:var(--shadow-elevated); max-height:92vh; overflow-y:auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-subtle);">
            <div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-heading); display:flex; align-items:center; gap:8px;">
                    <span>🤖 Approve & Assign AI Engine</span>
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Activate <strong id="approveRestName" style="color:var(--text-heading);"></strong> (<span id="approveRestMeta"></span>)
                </p>
            </div>
            <button type="button" onclick="closeApproveModal()" style="background:none; border:none; font-size:18px; color:var(--text-muted); cursor:pointer;">✕</button>
        </div>

        <form id="approveForm" method="POST" action="">
            @csrf

            <!-- 1. AI Provider Selection -->
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-weight: 800; display:flex; align-items:center; justify-content:space-between;">
                    <span>1. AI Engine Provider</span>
                    <span style="font-size: 10px; color: #10b981; font-weight: 700;">Zero Rate-Limit Shared Congestion</span>
                </label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 6px;">
                    <label style="display: flex; flex-direction: column; align-items: center; padding: 10px; border-radius: 12px; border: 1.5px solid var(--brand-primary); background: var(--brand-primary-light); cursor: pointer; text-align: center;" id="providerGroqBox">
                        <input type="radio" name="ai_provider" value="groq" checked onchange="updateProviderSelection('groq')" style="margin-bottom: 4px;">
                        <span style="font-weight: 800; font-size: 12px; color: var(--text-heading);">Groq</span>
                        <span style="font-size: 10px; color: #10b981; font-weight: 700;">Fast & Cheap</span>
                    </label>

                    <label style="display: flex; flex-direction: column; align-items: center; padding: 10px; border-radius: 12px; border: 1.5px solid var(--border-card); background: var(--bg-canvas); cursor: pointer; text-align: center;" id="providerGeminiBox">
                        <input type="radio" name="ai_provider" value="gemini" onchange="updateProviderSelection('gemini')" style="margin-bottom: 4px;">
                        <span style="font-weight: 800; font-size: 12px; color: var(--text-heading);">Gemini</span>
                        <span style="font-size: 10px; color: var(--text-muted);">1.5 Flash</span>
                    </label>

                    <label style="display: flex; flex-direction: column; align-items: center; padding: 10px; border-radius: 12px; border: 1.5px solid var(--border-card); background: var(--bg-canvas); cursor: pointer; text-align: center;" id="providerOpenAiBox">
                        <input type="radio" name="ai_provider" value="openai" onchange="updateProviderSelection('openai')" style="margin-bottom: 4px;">
                        <span style="font-weight: 800; font-size: 12px; color: var(--text-heading);">OpenAI</span>
                        <span style="font-size: 10px; color: var(--text-muted);">GPT-4o-mini</span>
                    </label>
                </div>
            </div>

            <!-- 2. Dedicated API Key Assignment -->
            <div class="form-group" style="margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <label class="form-label" style="font-weight: 800; margin-bottom: 0;">2. Dedicated API Key</label>
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--brand-primary); cursor: pointer; font-weight: 700;">
                        <input type="checkbox" name="use_platform_key" id="usePlatformKeyToggle" value="1" checked onchange="toggleApiKeyInput()">
                        <span>Use Platform Default Master Key</span>
                    </label>
                </div>

                <input type="text" name="ai_api_key" id="approveAiKeyInput" class="form-input" placeholder="e.g. gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" style="background: var(--bg-canvas); color: var(--text-muted);" disabled>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;" id="apiKeyHint">
                    🔒 Currently using platform master key. Uncheck the box above to assign an isolated API key for this restaurant.
                </div>
            </div>

            <!-- 3. AI Model & Subscription Plan -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-weight: 700;">AI Model</label>
                    <select name="ai_model" id="approveAiModelSelect" class="form-select">
                        <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile</option>
                        <option value="llama-3.1-8b-instant">llama-3.1-8b-instant</option>
                        <option value="gemini-1.5-flash">gemini-1.5-flash</option>
                        <option value="gpt-4o-mini">gpt-4o-mini</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-weight: 700;">Subscription Tier</label>
                    <select name="plan" id="approvePlanSelect" class="form-select">
                        @if(isset($plans))
                            @foreach($plans as $pl)
                                <option value="{{ $pl->slug }}">{{ $pl->name }}</option>
                            @endforeach
                        @else
                            <option value="starter">Starter</option>
                            <option value="basic">Basic</option>
                            <option value="pro">Pro</option>
                        @endif
                    </select>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--border-subtle);">
                <button type="button" class="btn btn-secondary" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" class="btn btn-success" style="font-weight: 800; padding: 9px 18px;">✓ Approve & Activate Restaurant</button>
            </div>
        </form>
    </div>
</div>

<script>
function rejectPrompt(id, name) {
    document.getElementById('rejectRestName').textContent = name;
    document.getElementById('rejectForm').action = '/admin/restaurant/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

function openApproveModal(id, name, owner, city, plan) {
    document.getElementById('approveRestName').textContent = name;
    document.getElementById('approveRestMeta').textContent = owner + ' • ' + city;
    document.getElementById('approveForm').action = '/admin/restaurant/' + id + '/approve';

    const planSelect = document.getElementById('approvePlanSelect');
    if (planSelect && plan) {
        planSelect.value = plan;
    }

    // Default to Groq and platform master key
    updateProviderSelection('groq');
    const chk = document.getElementById('usePlatformKeyToggle');
    if (chk) {
        chk.checked = true;
        toggleApiKeyInput();
    }

    document.getElementById('approveModal').style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
}

function toggleApiKeyInput() {
    const chk = document.getElementById('usePlatformKeyToggle');
    const inp = document.getElementById('approveAiKeyInput');
    const hint = document.getElementById('apiKeyHint');

    if (chk && inp) {
        if (chk.checked) {
            inp.disabled = true;
            inp.value = '';
            inp.style.background = 'var(--bg-canvas)';
            inp.style.color = 'var(--text-muted)';
            if (hint) hint.innerHTML = '🔒 Currently using platform master key. Uncheck above to assign an isolated dedicated API key for this restaurant.';
        } else {
            inp.disabled = false;
            inp.style.background = 'var(--input-bg)';
            inp.style.color = 'var(--text-heading)';
            inp.focus();
            if (hint) hint.innerHTML = '⚡ Enter dedicated API key to isolate rate limits and tokens for this restaurant.';
        }
    }
}

function updateProviderSelection(provider) {
    const groqBox = document.getElementById('providerGroqBox');
    const geminiBox = document.getElementById('providerGeminiBox');
    const openaiBox = document.getElementById('providerOpenAiBox');
    const modelSelect = document.getElementById('approveAiModelSelect');
    const inp = document.getElementById('approveAiKeyInput');

    [groqBox, geminiBox, openaiBox].forEach(b => {
        if (b) {
            b.style.borderColor = 'var(--border-card)';
            b.style.background = 'var(--bg-canvas)';
        }
    });

    if (provider === 'groq') {
        if (groqBox) {
            groqBox.style.borderColor = 'var(--brand-primary)';
            groqBox.style.background = 'var(--brand-primary-light)';
        }
        if (modelSelect) modelSelect.value = 'llama-3.3-70b-versatile';
        if (inp) inp.placeholder = 'e.g. gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    } else if (provider === 'gemini') {
        if (geminiBox) {
            geminiBox.style.borderColor = 'var(--brand-primary)';
            geminiBox.style.background = 'var(--brand-primary-light)';
        }
        if (modelSelect) modelSelect.value = 'gemini-1.5-flash';
        if (inp) inp.placeholder = 'e.g. AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    } else if (provider === 'openai') {
        if (openaiBox) {
            openaiBox.style.borderColor = 'var(--brand-primary)';
            openaiBox.style.background = 'var(--brand-primary-light)';
        }
        if (modelSelect) modelSelect.value = 'gpt-4o-mini';
        if (inp) inp.placeholder = 'e.g. sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxx';
    }
}
</script>
@endsection

