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
                <div style="position:relative;">
                    <input type="password" id="admin_current_password" name="current_password" class="form-input" required placeholder="Enter current password" style="padding-right:2.8rem;">
                    <button type="button" onclick="toggleAdminField('admin_current_password','adminEye1')" tabindex="-1"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:4px;">
                        <svg id="adminEye1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">New Master Password *</label>
                <div style="position:relative;">
                    <input type="password" id="admin_new_password" name="new_password" class="form-input" required minlength="8" placeholder="Minimum 8 characters" style="padding-right:2.8rem;">
                    <button type="button" onclick="toggleAdminField('admin_new_password','adminEye2')" tabindex="-1"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:4px;">
                        <svg id="adminEye2" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
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
                    <div style="position:relative;">
                        <input type="password" id="admin_pin" name="pin" class="form-input" value="{{ $storedPin }}" placeholder="e.g. 849201" style="padding-right:2.8rem;">
                        <button type="button" onclick="toggleAdminField('admin_pin','adminEye3')" tabindex="-1"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:4px;">
                            <svg id="adminEye3" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
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

const eyeOpen  = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
const eyeClose = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;

function toggleAdminField(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = eyeClose;
    } else {
        input.type = 'password';
        icon.innerHTML = eyeOpen;
    }
}
</script>
@endsection
