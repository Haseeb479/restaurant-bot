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
                                    <form method="POST" action="{{ route('admin.restaurant.approve', $p->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve and activate {{ addslashes($p->name) }}?')">
                                            Approve & Activate ✓
                                        </button>
                                    </form>
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
<div id="rejectModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--card-bg); border-radius:16px; padding:24px; width:460px; max-width:90%; border:1px solid var(--border-color); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);">
        <h3 style="margin-bottom:6px; font-size: 18px;">Reject Application</h3>
        <p style="font-size:12px; color:var(--text-secondary); margin-bottom:16px;">Specify the reason for rejection for <strong id="rejectRestName"></strong>.</p>
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

<script>
function rejectPrompt(id, name) {
    document.getElementById('rejectRestName').textContent = name;
    document.getElementById('rejectForm').action = '/admin/restaurant/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
@endsection
