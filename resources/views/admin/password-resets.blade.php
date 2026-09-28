@extends('layouts.admin')
@section('title', 'Owner Password Reset Requests')
@section('header_title', 'Owner Password Reset Requests')
@section('header_subtitle', 'Review and process password reset requests submitted by restaurant owners')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Password Reset Requests ({{ $requests->total() }})</h3>
            <p>Verification queue for restaurant owners requesting access recovery</p>
        </div>
        <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary">← Back to Restaurants</a>
    </div>

    @if($requests->isEmpty())
        <div style="text-align: center; padding: 50px 20px; color: var(--text-secondary);">
            <div style="font-size: 40px; margin-bottom: 12px;">✅</div>
            <h4 style="font-size: 16px; font-weight: 700; color: var(--text-primary);">No Pending Requests!</h4>
            <p style="font-size: 12px; margin-top: 4px;">There are currently no password reset requests from restaurant owners.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Restaurant & Verification Contact</th>
                        <th>Status</th>
                        <th>Requested At</th>
                        <th>Resolution & Notes</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                        @php
                            $targetRest = $req->restaurant ?: \App\Models\Restaurant::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($req->restaurant_name)) . '%'])->first();
                        @endphp
                        <tr>
                            <td>
                                <strong style="font-size: 13.5px; color: var(--text-primary);">{{ $req->restaurant_name }}</strong>
                                @if($targetRest)
                                    <span class="badge badge-green" style="margin-left: 6px;">Matched #{{ $targetRest->id }}</span>
                                @else
                                    <span class="badge badge-yellow" style="margin-left: 6px;">Unlinked</span>
                                @endif
                                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 3px;">
                                    ✉️ {{ $req->email }} &nbsp;|&nbsp; 📱 <code>{{ $req->phone }}</code>
                                </div>
                            </td>
                            <td>
                                @if($req->status === 'pending')
                                    <span class="badge badge-yellow">● Pending Review</span>
                                @elseif($req->status === 'resolved')
                                    <span class="badge badge-green">✓ Resolved</span>
                                @else
                                    <span class="badge badge-red">✕ Rejected</span>
                                @endif
                            </td>
                            <td style="font-size: 11.5px; color: var(--text-secondary); white-space: nowrap;">
                                {{ $req->created_at->format('d M Y, H:i') }}
                                <div style="font-size: 10.5px;">{{ $req->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($req->status === 'resolved')
                                    <div style="font-size: 11.5px; color: #10b981; font-weight: 700;">
                                        ✓ Dispatched to Owner Contact
                                    </div>
                                @endif
                                @if($req->admin_notes)
                                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                        {{ $req->admin_notes }}
                                    </div>
                                @endif
                                @if($req->status !== 'resolved' && !$req->admin_notes)
                                    <span style="color: var(--text-secondary); font-size: 11px;">Awaiting action</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                @if($req->status === 'pending')
                                    <div style="display: inline-flex; gap: 6px; align-items: center;">
                                        <!-- Resolve Modal/Form -->
                                        <form method="POST" action="{{ route('admin.password-resets.resolve', $req->id) }}" style="display: inline-flex;" onsubmit="return confirm('Generate a secure temporary password and dispatch directly to {{ addslashes($req->restaurant_name) }}\'s contact?');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" title="Generate & dispatch to owner">
                                                🔑 Dispatch Reset
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.password-resets.reject', $req->id) }}" style="display: inline;" onsubmit="return confirm('Reject this reset request?');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm" title="Reject request">
                                                ✕ Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span style="font-size: 11px; color: var(--text-secondary);">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 14px;">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
