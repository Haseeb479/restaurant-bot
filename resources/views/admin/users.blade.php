@extends('layouts.admin')
@section('title', 'Users')
@section('header_title', 'Restaurant Owners & Users')
@section('header_subtitle', 'Manage restaurant owner accounts, credentials, and tenant access')

@section('content')

<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Registered Restaurant Accounts ({{ $restaurants->count() }})</h3>
            <p>Access credentials and contact information for each restaurant owner</p>
        </div>
        <a href="{{ route('admin.create-restaurant') }}" class="btn btn-primary">
            + Register New Owner
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Owner Phone</th>
                    <th>Bot WhatsApp</th>
                    <th>Plan</th>
                    <th>Account Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($restaurants as $r)
                <tr>
                    <td>
                        <strong>{{ $r->name }}</strong>
                        <div style="font-size: 11px; color: #64748b;">{{ $r->address ?: $r->city }}</div>
                    </td>
                    <td><code>{{ $r->owner_phone }}</code></td>
                    <td><code>{{ $r->whatsapp_number }}</code></td>
                    <td><span class="badge badge-blue" style="text-transform: uppercase;">{{ $r->plan }}</span></td>
                    <td>
                        @if($r->is_active)
                            <span class="badge badge-green">● Active Access</span>
                        @else
                            <span class="badge badge-red">○ Suspended</span>
                        @endif
                    </td>
                    <td>
                        <a href="/dashboard/{{ $r->id }}/login" class="btn btn-secondary" style="padding: 5px 12px; font-size: 12px;">
                            Sign-in as Owner ↗
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
