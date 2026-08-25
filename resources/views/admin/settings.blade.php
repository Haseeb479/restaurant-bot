@extends('layouts.admin')
@section('title', 'Super Admin Settings')
@section('header_title', 'Super Admin & Security Settings')
@section('header_subtitle', 'Master password rotation, 2-Factor Authentication PIN, and platform configuration')

@section('content')
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 22px; max-width: 950px;">
    <!-- Master Password Rotation -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Master Password Rotation</h3>
                <p>Change your Super Admin account master password</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.update-settings') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Current Master Password *</label>
                <input type="password" name="current_password" class="form-input" required placeholder="Enter current password">
            </div>

            <div class="form-group">
                <label class="form-label">New Master Password *</label>
                <input type="password" name="new_password" class="form-input" required minlength="8" placeholder="Minimum 8 characters">
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 16px 0;">

            <div class="form-group">
                <label class="form-label">Platform Timezone</label>
                <input type="text" name="timezone" class="form-input" value="{{ $timezone }}" placeholder="e.g. Asia/Karachi">
            </div>

            <div class="form-group">
                <label class="form-label">Currency Symbol</label>
                <input type="text" name="currency_symbol" class="form-input" value="{{ $currency }}" placeholder="Rs.">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Update Credentials & Settings</button>
        </form>
    </div>

    <!-- 2-Factor Authentication & IP Whitelisting -->
    <div>
        <!-- 2FA Box -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Two-Factor Authentication (2FA)</h3>
                    <p>Enforce an extra security PIN on Super Admin login</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.settings.2fa') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">2FA Status</label>
                    <select name="enable" class="form-select" onchange="togglePinInput(this.value)">
                        <option value="0" {{ !$twoFaEnabled ? 'selected' : '' }}>Disabled (Password Only)</option>
                        <option value="1" {{ $twoFaEnabled ? 'selected' : '' }}>Enabled (Password + Security PIN)</option>
                    </select>
                </div>

                <div class="form-group" id="pinGroup" style="{{ $twoFaEnabled ? '' : 'display:none;' }}">
                    <label class="form-label">Security PIN (4 to 8 digits) *</label>
                    <input type="password" name="pin" class="form-input" value="{{ $storedPin }}" placeholder="e.g. 849201">
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">You will be prompted for this PIN alongside your password upon login.</div>
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">Save 2FA Security</button>
            </form>
        </div>

        <!-- IP Whitelisting -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>IP Access Whitelist</h3>
                    <p>Restrict Super Admin access to specific IP addresses (Optional)</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.update-settings') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Allowed IP Addresses (Comma-separated)</label>
                    <input type="text" name="ip_whitelist" class="form-input" value="{{ $ipWhitelist }}" placeholder="Leave blank to allow any IP">
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">Current your IP: <code>{{ request()->ip() }}</code></div>
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">Save IP Restrictions</button>
            </form>
        </div>
    </div>
</div>

<script>
function togglePinInput(val) {
    document.getElementById('pinGroup').style.display = val === '1' ? 'block' : 'none';
}
</script>
@endsection
