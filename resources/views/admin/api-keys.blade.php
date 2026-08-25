@extends('layouts.admin')
@section('title', 'API Key Management')
@section('header_title', 'Developer API Keys')
@section('header_subtitle', 'Generate, rotate, and manage API keys for external integrations & mobile applications')

@section('content')
<div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 22px;">
    <!-- Active API Keys Table -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Active Platform API Keys</h3>
                <p>Keys used for external REST endpoints (Orders, Menus, Webhooks)</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Key Label</th>
                        <th>API Token</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keys as $k)
                        <tr>
                            <td><strong>{{ $k->name }}</strong></td>
                            <td><code>{{ Str::mask($k->key, '*', 8, -4) }}</code></td>
                            <td>
                                @if($k->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Revoked</span>
                                @endif
                            </td>
                            <td style="font-size: 11.5px; color: var(--text-secondary);">{{ $k->created_at->format('d M Y') }}</td>
                            <td style="text-align: right;">
                                <form method="POST" action="{{ route('admin.api-keys.delete', $k->id) }}" onsubmit="return confirm('Revoke and delete this API key?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Revoke ✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 25px;">No external API keys generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 14px;">
            {{ $keys->links() }}
        </div>
    </div>

    <!-- Generate API Key Form -->
    <div>
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Generate New API Key</h3>
                    <p>Create a secure integration key</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.api-keys.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Integration / App Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. POS Sync App, Mobile App V1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Permission Scopes</label>
                    <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px;">
                        <label><input type="checkbox" checked disabled> Read & Write Orders</label>
                        <label><input type="checkbox" checked disabled> Read & Write Menu Catalog</label>
                        <label><input type="checkbox" checked disabled> Webhook Delivery</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">🔑 Generate Key</button>
            </form>
        </div>
    </div>
</div>
@endsection
