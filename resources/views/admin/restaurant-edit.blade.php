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
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-input" value="{{ old('city', $r->city) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Subscription Tier *</label>
                    <select name="plan" class="form-select" required>
                        @foreach($plans as $p)
                            <option value="{{ $p->slug }}" {{ (old('plan', $r->plan) === $p->slug) ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
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
                    'order_tracking' => true,
                    'customer_notifications' => true,
                    'ai_suggestions' => true,
                    'human_handover' => true,
                    'voice_notes' => true,
                    'deal_broadcast' => true,
                ];
            @endphp
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer;">
                    <input type="checkbox" name="feature_order_tracking" value="1" {{ !empty($feats['order_tracking']) ? 'checked' : '' }}>
                    <span>Live Order Tracking</span>
                </label>
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

            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 12px; color: #4f46e5;">3. AI Model & Limits</h4>
            @php
                $aiConfig = $r->ai_config ?? [
                    'model' => 'gemini-1.5-flash',
                    'temperature' => 0.7,
                    'system_prompt' => '',
                ];
            @endphp
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">AI Engine Model</label>
                    <select name="ai_model" class="form-select">
                        <option value="gemini-1.5-flash" {{ ($aiConfig['model'] ?? '') === 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Fast & Low Cost)</option>
                        <option value="gemini-1.5-pro" {{ ($aiConfig['model'] ?? '') === 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro (Deep Reasoning)</option>
                        <option value="gpt-4o-mini" {{ ($aiConfig['model'] ?? '') === 'gpt-4o-mini' ? 'selected' : '' }}>OpenAI GPT-4o Mini</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Temperature (Creativity 0.0 - 1.0)</label>
                    <input type="number" step="0.1" min="0" max="1" name="ai_temperature" class="form-input" value="{{ $aiConfig['temperature'] ?? 0.7 }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Monthly Message Quota (Rate Limit)</label>
                <input type="number" name="rate_limit_per_month" class="form-input" value="{{ $r->rate_limit_per_month ?? 1000 }}">
            </div>

            <div class="form-group">
                <label class="form-label">Custom AI System Prompt Override (Optional)</label>
                <textarea name="ai_system_prompt" class="form-textarea" rows="3" placeholder="Leave blank to use global default prompt...">{{ $aiConfig['system_prompt'] ?? '' }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-size: 13px;">Save All Changes</button>
        </form>
    </div>

    <!-- Side Actions & Security Controls -->
    <div>
        <!-- Reset Password Card -->
        <div class="panel-card">
            <div class="panel-title" style="margin-bottom: 12px;">
                <h3>Reset Owner Password</h3>
                <p>Generate a new login credential for the restaurant owner</p>
            </div>
            <form method="POST" action="{{ route('admin.restaurant.reset-password', $r->id) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">New Password (or leave blank to auto-generate)</label>
                    <input type="text" name="new_password" class="form-input" placeholder="e.g. Pass@1234">
                </div>
                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">🔑 Reset Credentials</button>
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
