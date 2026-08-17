@extends('layouts.dashboard')
@section('content')

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin: 1.5rem 0;
}
.stat-card {
    background: #fff;
    border: 1px solid #e8e8e4;
    border-radius: 14px;
    padding: 16px;
}
.stat-label { font-size: 12px; color: #888; }
.stat-value { font-size: 22px; font-weight: 600; margin: 6px 0; }
.stat-sub   { font-size: 11px; color: #aaa; }

.alert {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 1.5rem;
    font-size: 14px;
}

.card {
    background: #fff;
    border: 1px solid #e8e8e4;
    border-radius: 16px;
    overflow: hidden;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 18px;
    border-bottom: 1px solid #eee;
    flex-wrap: wrap;
    gap: 12px;
}
.card-header h2 { font-size: 16px; font-weight: 600; }

.btn {
    background: #0e0e10;
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    cursor: pointer;
    border: none;
}
.btn:hover { background: #2a2a2e; }

.table { width: 100%; border-collapse: collapse; }
.table th {
    text-align: left;
    font-size: 11px;
    color: #888;
    padding: 12px;
    background: #fafafa;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.table td {
    padding: 12px;
    border-top: 1px solid #f0f0f0;
    vertical-align: top;
    font-size: 13px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
}
.status-pending          { background:#f3f4f6; color:#374151; }
.status-confirmed        { background:#ecfeff; color:#155e75; }
.status-preparing        { background:#fff7ed; color:#9a3412; }
.status-out_for_delivery { background:#f0fdf4; color:#166534; }
.status-delivered        { background:#dcfce7; color:#166534; }
.status-cancelled        { background:#fee2e2; color:#991b1b; }

.tracking-code {
    font-family: monospace;
    font-size: 12px;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 6px;
    color: #374151;
    font-weight: 600;
}

@media(max-width: 900px){
    .stats-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="page-header">
    <h1>Live Orders</h1>
    <p>{{ now()->format('l, d F Y') }} — Auto-refreshes every 30 seconds</p>
</div>

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Today's Orders</div>
        <div class="stat-value">{{ $today->count() }}</div>
        <div class="stat-sub">since midnight</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Revenue</div>
        <div class="stat-value">Rs. {{ number_format($today->sum('total'), 0) }}</div>
        <div class="stat-sub">today</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active</div>
        <div class="stat-value">
            {{ $today->whereIn('status', ['pending','confirmed','preparing','out_for_delivery'])->count() }}
        </div>
        <div class="stat-sub">needs action</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Delivered</div>
        <div class="stat-value">{{ $today->where('status','delivered')->count() }}</div>
        <div class="stat-sub">completed</div>
    </div>
</div>

{{-- NEW ORDER ALERT --}}
@php $pendingOrders = $today->where('status','pending') @endphp
@if($pendingOrders->count() > 0)
<div class="alert">
    🔔 <strong>{{ $pendingOrders->count() }} new order(s) waiting for confirmation!</strong>
</div>
@endif

{{-- ORDERS TABLE --}}
<div class="card">
    <div class="card-header">
        <h2>All Orders</h2>
        <button class="btn" onclick="location.reload()">↻ Refresh</button>
    </div>

    @if($orders->count() === 0)
        <div style="text-align:center; padding:40px; color:#888; font-size:14px;">
            No orders yet. Orders placed via WhatsApp will appear here.
        </div>
    @else
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tracking</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Rider Info</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td><strong>#{{ $order->id }}</strong></td>

                    <td>
                        <a href="{{ url('/track/' . $order->tracking_code) }}" target="_blank" class="tracking-code" title="View Live Tracking Page">
                            {{ $order->tracking_code }} ↗
                        </a>
                    </td>

                    <td>
                        @if($order->customer_name)
                            <strong>{{ $order->customer_name }}</strong><br>
                        @endif
                        <span style="font-size:12px;color:#888;">{{ $order->customer_phone }}</span><br>
                        <span style="font-size:12px;">📍 {{ Str::limit($order->delivery_address, 35) }}</span>
                    </td>

                    <td>
                        @forelse($order->items as $item)
                            <div>
                                {{ $item->name }}
                                @if($item->size) <span style="font-size:11px;color:#888;">({{ $item->size }})</span> @endif
                                × {{ $item->quantity }}
                            </div>
                        @empty
                            <span style="color:#aaa;font-size:12px;">{{ Str::limit($order->notes, 40) }}</span>
                        @endforelse
                    </td>

                    <td><strong>Rs. {{ number_format($order->total, 0) }}</strong></td>

                    <td>
                        @if($order->rider_name || $order->rider_phone)
                            <div style="font-size:12px; background:#f0fdf4; border:1px solid #bbf7d0; padding:4px 8px; border-radius:6px; color:#166534;">
                                <strong>🛵 {{ $order->rider_name ?: 'Rider' }}</strong><br>
                                <span style="font-size:11px;">{{ $order->rider_phone }}</span>
                            </div>
                        @else
                            <span style="font-size:12px; color:#aaa;">Not assigned</span>
                        @endif
                    </td>

                    <td>
                        <span class="status-badge status-{{ $order->status }}">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </td>

                    <td style="font-size:12px;color:#888;">
                        {{ $order->created_at->diffForHumans() }}
                    </td>

                    <td>
                        @if(!in_array($order->status, ['delivered','cancelled']))
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <form id="form-order-{{ $order->id }}" method="POST" action="{{ route('dashboard.update-status', [$restaurant->id, $order->id]) }}">
                                @csrf
                                <input type="hidden" name="rider_name" id="rider-name-{{ $order->id }}" value="{{ $order->rider_name }}">
                                <input type="hidden" name="rider_phone" id="rider-phone-{{ $order->id }}" value="{{ $order->rider_phone }}">
                                
                                <select name="status" onchange="handleStatusChange(this, '{{ $order->id }}', '{{ $order->tracking_code }}')"
                                        style="padding:6px 8px;border-radius:6px;border:1px solid #e8e8e4;font-size:12px;cursor:pointer;width:100%;">
                                    <option value="pending"          {{ $order->status==='pending'          ? 'selected':'' }}>Pending</option>
                                    <option value="confirmed"        {{ $order->status==='confirmed'        ? 'selected':'' }}>Confirm Order</option>
                                    <option value="preparing"        {{ $order->status==='preparing'        ? 'selected':'' }}>Start Preparing</option>
                                    <option value="out_for_delivery" {{ $order->status==='out_for_delivery' ? 'selected':'' }}>🛵 Assign Rider & Send</option>
                                    <option value="delivered"        {{ $order->status==='delivered'        ? 'selected':'' }}>Mark Delivered</option>
                                    <option value="cancelled">Cancel Order</option>
                                </select>
                            </form>
                        </div>
                        @else
                            <span style="color:#166534; font-size:12px; font-weight:600;">✓ Completed</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="padding:1rem;">
        {{ $orders->links() }}
    </div>
    @endif
</div>

<!-- Modal for Assigning Rider -->
<div id="rider-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:14px; padding:24px; max-width:400px; width:90%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="font-size:16px; font-weight:700; margin-bottom:6px;">🛵 Assign Rider & Send Order</h3>
        <p style="font-size:12px; color:#666; margin-bottom:16px;">The customer will automatically receive a WhatsApp alert with the rider's contact details and live tracking link.</p>
        
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Rider Name:</label>
            <input type="text" id="modal-rider-name" placeholder="e.g. Ali Raza" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px;">
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Rider Phone Number:</label>
            <input type="text" id="modal-rider-phone" placeholder="e.g. 03001234567" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px;">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
            <button onclick="closeRiderModal()" style="padding:8px 14px; border:1px solid #ddd; background:#f9fafb; border-radius:8px; font-size:12px; cursor:pointer;">Cancel</button>
            <button onclick="confirmRiderDispatch()" style="padding:8px 16px; background:#0e0e10; color:#fff; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">Dispatch Order 🛵</button>
        </div>
    </div>
</div>

<script>
    let activeOrderId = null;

    function handleStatusChange(selectElem, orderId, trackingCode) {
        if (selectElem.value === 'out_for_delivery') {
            activeOrderId = orderId;
            const currentRiderName = document.getElementById('rider-name-' + orderId).value;
            const currentRiderPhone = document.getElementById('rider-phone-' + orderId).value;
            document.getElementById('modal-rider-name').value = currentRiderName || '';
            document.getElementById('modal-rider-phone').value = currentRiderPhone || '';
            document.getElementById('rider-modal').style.display = 'flex';
        } else {
            document.getElementById('form-order-' + orderId).submit();
        }
    }

    function closeRiderModal() {
        document.getElementById('rider-modal').style.display = 'none';
        if (activeOrderId) {
            location.reload();
        }
    }

    function confirmRiderDispatch() {
        if (!activeOrderId) return;
        const name = document.getElementById('modal-rider-name').value.trim();
        const phone = document.getElementById('modal-rider-phone').value.trim();
        
        document.getElementById('rider-name-' + activeOrderId).value = name;
        document.getElementById('rider-phone-' + activeOrderId).value = phone;
        document.getElementById('form-order-' + activeOrderId).submit();
    }

    // Auto-refresh every 30 seconds
    setTimeout(() => location.reload(), 30000);
</script>

@endsection