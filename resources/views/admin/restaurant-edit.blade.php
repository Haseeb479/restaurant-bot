@extends('layouts.admin')
@section('title', 'Edit ' . $r->name)
@section('header_title', 'Configure ' . $r->name)
@section('header_subtitle', 'Restaurant Details, Bot Feature Flags & Credential Controls')

@section('content')
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 22px;">
    <!-- Main Edit Form -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Restaurant Profile & Bot Settings</h3>
                <p>Modify basic details, operating parameters, and bot intelligence</p>
            </div>
            <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary btn-sm">← Back</a>
        </div>

        <form method="POST" action="{{ route('admin.restaurant.update', $r->id) }}">
            @csrf
            
            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 12px; color: #4f46e5;">1. Basic Information</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Restaurant Name *</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $r->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">WhatsApp Bot Number *</label>
                    <input type="text" name="whatsapp_number" class="form-input" value="{{ old('whatsapp_number', $r->whatsapp_number) }}" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Owner Phone *</label>
                    <input type="text" name="owner_phone" class="form-input" value="{{ old('owner_phone', $r->owner_phone) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Manager / Staff Phone</label>
                    <input type="text" name="manager_phone" class="form-input" value="{{ old('manager_phone', $r->manager_phone) }}">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Owner Email Address</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email', $r->email) }}" placeholder="owner@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-input" value="{{ old('city', $r->city) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Subscription Tier *</label>
                <select name="plan" class="form-select" required>
                    @foreach($plans as $p)
                        <option value="{{ $p->slug }}" {{ (old('plan', $r->plan) === $p->slug) ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Full Address</label>
                <textarea name="address" class="form-textarea" rows="2">{{ old('address', $r->address) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Delivery Fee (PKR)</label>
                    <input type="number" step="0.01" name="delivery_charge" class="form-input" value="{{ old('delivery_charge', $r->delivery_charge) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Order (PKR)</label>
                    <input type="number" step="0.01" name="minimum_order" class="form-input" value="{{ old('minimum_order', $r->minimum_order) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Delivery Areas (Comma separated)</label>
                <input type="text" name="delivery_areas" class="form-input" value="{{ old('delivery_areas', $r->delivery_areas) }}" placeholder="e.g. F-7, F-8, Blue Area, G-9">
            </div>

            <div class="form-group">
                <label class="form-label">Greeting Message</label>
                <textarea name="greeting_message" class="form-textarea" rows="2" placeholder="Custom welcome text for incoming customers">{{ old('greeting_message', $r->greeting_message) }}</textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 12px; color: #4f46e5;">2. Bot Feature Flags & Capabilities</h4>
            @php
                $feats = $r->features ?? [
                    'customer_notifications' => true,
                    'ai_suggestions' => true,
                    'human_handover' => true,
                    'voice_notes' => true,
                    'deal_broadcast' => true,
                ];
            @endphp
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer;">
                    <input type="checkbox" name="feature_customer_notifications" value="1" {{ !empty($feats['customer_notifications']) ? 'checked' : '' }}>
                    <span>Order Status Updates</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer;">
                    <input type="checkbox" name="feature_ai_suggestions" value="1" {{ !empty($feats['ai_suggestions']) ? 'checked' : '' }}>
                    <span>AI Upsell & Recommendations</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer;">
                    <input type="checkbox" name="feature_human_handover" value="1" {{ !empty($feats['human_handover']) ? 'checked' : '' }}>
                    <span>Human Agent Handover</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer;">
                    <input type="checkbox" name="feature_voice_notes" value="1" {{ !empty($feats['voice_notes']) ? 'checked' : '' }}>
                    <span>Voice Note Audio Transcripts</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer;">
                    <input type="checkbox" name="feature_deal_broadcast" value="1" {{ !empty($feats['deal_broadcast']) ? 'checked' : '' }}>
                    <span>Customer Broadcast Deals</span>
                </label>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 12px; color: #4f46e5;">3. AI Engine, Dedicated API Key & Limits</h4>
            @php
                $aiConfig = $r->ai_config ?? [];
                $currentProvider = $r->getAiProvider();
                $hasCustomKey = $r->hasCustomAiKey();
                $savedKey = $aiConfig['api_key'] ?? '';
            @endphp

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div class="form-group">
                    <label class="form-label">AI Engine Provider</label>
                    <select name="ai_provider" id="editAiProviderSelect" class="form-select" onchange="onEditProviderChange(this.value)">
                        <option value="groq" {{ $currentProvider === 'groq' ? 'selected' : '' }}>Groq (Recommended - Fast & Free/Cheap)</option>
                        <option value="gemini" {{ $currentProvider === 'gemini' ? 'selected' : '' }}>Google Gemini 1.5 Flash</option>
                        <option value="openai" {{ $currentProvider === 'openai' ? 'selected' : '' }}>OpenAI</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">AI Engine Model</label>
                    <select name="ai_model" id="editAiModelSelect" class="form-select">
                        <option value="llama-3.3-70b-versatile" {{ ($r->getAiModel() === 'llama-3.3-70b-versatile') ? 'selected' : '' }}>llama-3.3-70b-versatile (Groq)</option>
                        <option value="llama-3.1-8b-instant" {{ ($r->getAiModel() === 'llama-3.1-8b-instant') ? 'selected' : '' }}>llama-3.1-8b-instant (Groq)</option>
                        <option value="gemini-1.5-flash" {{ ($r->getAiModel() === 'gemini-1.5-flash') ? 'selected' : '' }}>gemini-1.5-flash (Google)</option>
                        <option value="gemini-1.5-pro" {{ ($r->getAiModel() === 'gemini-1.5-pro') ? 'selected' : '' }}>gemini-1.5-pro (Google)</option>
                        <option value="gpt-4o-mini" {{ ($r->getAiModel() === 'gpt-4o-mini') ? 'selected' : '' }}>gpt-4o-mini (OpenAI)</option>
                    </select>
                </div>
            </div>

            <!-- Dedicated API Key Field -->
            <div class="form-group" style="margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <label class="form-label" style="margin-bottom: 0;">Dedicated API Key for this Restaurant</label>
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--brand-primary); cursor: pointer; font-weight: 700;">
                        <input type="checkbox" name="use_platform_key" id="editUsePlatformKeyToggle" value="1" {{ !$hasCustomKey ? 'checked' : '' }} onchange="onToggleEditApiKey()">
                        <span>Use Platform Default Master Key</span>
                    </label>
                </div>
                <input type="text" name="ai_api_key" id="editAiApiKeyInput" class="form-input" value="{{ $savedKey }}" placeholder="e.g. gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" style="{{ !$hasCustomKey ? 'background: var(--bg-canvas); color: var(--text-muted);' : '' }}" {{ !$hasCustomKey ? 'disabled' : '' }}>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;" id="editApiKeyHint">
                    @if($hasCustomKey)
                        ⚡ Dedicated API key active. Rate limits & tokens are 100% isolated for this restaurant.
                    @else
                        🔒 Currently using platform master key. Uncheck above to assign an isolated dedicated API key.
                    @endif
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Temperature (Creativity 0.0 - 1.0)</label>
                    <input type="number" step="0.1" min="0" max="1" name="ai_temperature" class="form-input" value="{{ $aiConfig['temperature'] ?? 0.7 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly Message Quota (Rate Limit)</label>
                    <input type="number" name="rate_limit_per_month" class="form-input" value="{{ $r->rate_limit_per_month ?? 1000 }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Custom AI System Prompt Override (Optional)</label>
                <textarea name="ai_system_prompt" class="form-textarea" rows="3" placeholder="Leave blank to use global default prompt...">{{ $aiConfig['system_prompt'] ?? '' }}</textarea>
            </div>

            <script>
            function onToggleEditApiKey() {
                const chk = document.getElementById('editUsePlatformKeyToggle');
                const inp = document.getElementById('editAiApiKeyInput');
                const hint = document.getElementById('editApiKeyHint');
                if (chk && inp) {
                    if (chk.checked) {
                        inp.disabled = true;
                        inp.style.background = 'var(--bg-canvas)';
                        inp.style.color = 'var(--text-muted)';
                        if (hint) hint.innerHTML = '🔒 Currently using platform master key. Uncheck above to assign an isolated dedicated API key.';
                    } else {
                        inp.disabled = false;
                        inp.style.background = 'var(--input-bg)';
                        inp.style.color = 'var(--text-heading)';
                        inp.focus();
                        if (hint) hint.innerHTML = '⚡ Enter dedicated API key to isolate rate limits and tokens for this restaurant.';
                    }
                }
            }

            function onEditProviderChange(provider) {
                const modelSelect = document.getElementById('editAiModelSelect');
                const inp = document.getElementById('editAiApiKeyInput');
                if (provider === 'groq') {
                    if (modelSelect) modelSelect.value = 'llama-3.3-70b-versatile';
                    if (inp) inp.placeholder = 'e.g. gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx';
                } else if (provider === 'gemini') {
                    if (modelSelect) modelSelect.value = 'gemini-1.5-flash';
                    if (inp) inp.placeholder = 'e.g. AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxxx';
                } else if (provider === 'openai') {
                    if (modelSelect) modelSelect.value = 'gpt-4o-mini';
                    if (inp) inp.placeholder = 'e.g. sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxx';
                }
            }
            </script>

            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-size: 13px;">Save All Changes</button>
        </form>
    </div>

    <!-- Side Actions & Security Controls -->
    <div>
        <!-- Reset Password Card -->
        <div class="panel-card">
            <div class="panel-title" style="margin-bottom: 12px;">
                <h3>Reset Owner Password</h3>
                <p>Generate and dispatch credentials to the owner</p>
            </div>
            <p style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.5;">
                In accordance with tenant privacy, clicking below generates a cryptographically random temporary password and sends it directly to the owner's WhatsApp/email. Superadmins do not see this password.
            </p>
            <form method="POST" action="{{ route('admin.restaurant.reset-password', $r->id) }}" onsubmit="return confirm('Generate and dispatch a temporary password to {{ addslashes($r->name) }}\'s registered contact?');">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; font-weight: 700;">🔑 Dispatch Reset Credentials</button>
            </form>
        </div>

        <!-- Bot Session & Error Reset -->
        <div class="panel-card">
            <div class="panel-title" style="margin-bottom: 12px;">
                <h3>Reset Bot Session</h3>
                <p>Clear errors and disconnect cached WhatsApp session</p>
            </div>
            <div style="margin-bottom: 10px;">
                Status: <strong>{{ $r->bot_status_label }}</strong>
                @if($r->last_error)
                    <div style="font-size: 11px; color: #ef4444; margin-top: 4px;">Error: {{ $r->last_error }}</div>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.restaurant.reset-bot', $r->id) }}">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">🔄 Clear Session & Errors</button>
            </form>
        </div>

        <!-- Clone Global Menu Template -->
        <div class="panel-card">
            <div class="panel-title" style="margin-bottom: 12px;">
                <h3>Clone Menu Template</h3>
                <p>Instantly copy standard items into this restaurant's menu</p>
            </div>
            @if($menuTemplates->isNotEmpty())
                <form method="POST" action="" id="cloneMenuForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Select Master Template</label>
                        <select id="menuTemplateSelect" class="form-select">
                            @foreach($menuTemplates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->items->count() }} items)</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-success" style="width: 100%; justify-content: center;" onclick="submitMenuClone('{{ $r->id }}')">📋 Clone Menu Items</button>
                </form>
            @else
                <p style="font-size: 11px; color: var(--text-secondary);">No master templates available. <a href="{{ route('admin.menu-templates') }}">Create one here</a>.</p>
            @endif
        </div>

        <!-- Plan Extension -->
        <div class="panel-card">
            <div class="panel-title" style="margin-bottom: 12px;">
                <h3>Extend Plan Validity</h3>
                <p>Current Expiry: <strong>{{ $r->plan_expires_at ? $r->plan_expires_at->format('d M Y') : 'Lifetime' }}</strong></p>
            </div>
            <form method="POST" action="{{ route('admin.extend-plan', $r->id) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Extend by (Months)</label>
                    <select name="months" class="form-select">
                        <option value="1">1 Month</option>
                        <option value="3">3 Months</option>
                        <option value="6">6 Months</option>
                        <option value="12">1 Year (12 Months)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">📅 Extend Subscription</button>
            </form>
        </div>
    </div>
</div>

<script>
function submitMenuClone(restaurantId) {
    const templateId = document.getElementById('menuTemplateSelect').value;
    if (confirm('Are you sure you want to clone items from this template into {{ addslashes($r->name) }}?')) {
        const form = document.getElementById('cloneMenuForm');
        form.action = '/admin/menu-templates/' + templateId + '/clone/' + restaurantId;
        form.submit();
    }
}
</script>
@endsection
