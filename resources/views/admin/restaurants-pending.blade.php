@extends('layouts.admin')
@section('title', 'Pending Restaurant Approvals')
@section('header_title', 'Pending Restaurant Approvals')
@section('header_subtitle', 'Review and approve/reject self-registered restaurants')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Approval Queue</h3>
            <p>Restaurants that have submitted registration and require verification before onboarding</p>
        </div>
        <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary">← Back to All Restaurants</a>
    </div>

    @if($pendingRestaurants->isEmpty())
        <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
            <div style="font-size: 36px; margin-bottom: 10px;">🎉</div>
            <h4 style="font-size: 16px; font-weight: 700; color: var(--text-primary);">All Caught Up!</h4>
            <p style="font-size: 12px; margin-top: 4px;">There are currently no pending restaurant registrations requiring verification.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Restaurant Name</th>
                        <th>WhatsApp Bot Number</th>
                        <th>Owner Contact</th>
                        <th>City / Address</th>
                        <th>Registered Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRestaurants as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->name }}</strong>
                            </td>
                            <td>
                                <code>{{ $p->whatsapp_number }}</code>
                            </td>
                            <td>
                                <div>{{ $p->owner_phone }}</div>
                            </td>
                            <td>
                                <div>{{ $p->city ?: 'Not specified' }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ Str::limit($p->address, 35) ?: 'No address' }}</div>
                            </td>
                            <td>
                                <div>{{ $p->created_at->format('d M Y, h:i A') }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ $p->created_at->diffForHumans() }}</div>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <form method="POST" action="{{ route('admin.restaurant.approve', $p->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Approve & Activate ✓</button>
                                    </form>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="rejectPrompt('{{ $p->id }}', '{{ addslashes($p->name) }}')">Reject ✕</button>
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
    <div style="background:var(--card-bg); border-radius:12px; padding:24px; width:440px; max-width:90%; border:1px solid var(--border-color);">
        <h3 style="margin-bottom:8px;">Reject Registration</h3>
        <p style="font-size:12px; color:var(--text-secondary); margin-bottom:16px;">Specify the reason for rejection for <strong id="rejectRestName"></strong>.</p>
        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label">Rejection Reason</label>
                <textarea name="reason" class="form-textarea" rows="3" required placeholder="e.g. Unverified phone number, duplicate business name..."></textarea>
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
