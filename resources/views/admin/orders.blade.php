<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0efe9; min-height: 100vh; }
        nav { background: #0e0e10; height: 54px; padding: 0 1.75rem; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .wm-icon { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; width: 22px; height: 22px; }
        .wm-sq { border-radius: 2px; }
        .wm-sq:nth-child(1),.wm-sq:nth-child(4) { background: #fff; }
        .wm-sq:nth-child(2),.wm-sq:nth-child(3) { background: rgba(255,255,255,0.25); }
        .brand-text { font-size: 13px; font-weight: 500; color: #fff; letter-spacing: -0.01em; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .back-btn { font-size: 12px; color: rgba(255,255,255,0.45); background: none; border: 0.5px solid rgba(255,255,255,0.12); border-radius: 7px; padding: 5px 14px; cursor: pointer; text-decoration: none; }
        .back-btn:hover { color: #fff; border-color: rgba(255,255,255,0.3); }
        .body { max-width: 1200px; margin: 1.75rem auto; padding: 0 1.25rem; }
        .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .section-title { font-size: 12px; font-weight: 500; color: #888; text-transform: uppercase; letter-spacing: 0.07em; }
        .table { background: #fff; border-radius: 12px; border: 0.5px solid #e8e8e4; overflow: hidden; }
        .t-head { display: grid; grid-template-columns: 1fr 2fr 1fr 1fr 1fr; padding: 10px 1.25rem; border-bottom: 0.5px solid #f0efe9; }
        .t-head span { font-size: 11px; font-weight: 500; color: #bbb; text-transform: uppercase; letter-spacing: 0.05em; }
        .t-row { display: grid; grid-template-columns: 1fr 2fr 1fr 1fr 1fr; padding: 14px 1.25rem; border-bottom: 0.5px solid #f5f5f2; align-items: center; }
        .t-row:last-child { border-bottom: none; }
        .order-id { font-size: 14px; font-weight: 500; color: #111; }
        .order-info { font-size: 13px; color: #666; }
        .restaurant-name { font-weight: 500; color: #111; }
        .status-pill { display: inline-flex; font-size: 11px; font-weight: 500; border-radius: 99px; padding: 3px 10px; }
        .status-new { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-preparing { background: #f3e8ff; color: #6b21a8; }
        .status-out_for_delivery { background: #fed7aa; color: #9a3412; }
        .status-delivered { background: #dcfce7; color: #14532d; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .view-btn { font-size: 11px; color: #555; background: #f5f5f2; border: 0.5px solid #e8e8e4; border-radius: 6px; padding: 4px 10px; cursor: pointer; text-decoration: none; }
        .view-btn:hover { background: #ececea; }
        .empty-state { text-align: center; padding: 3rem 1rem; color: #9ca3af; }
        .empty-state p { font-size: 15px; margin-top: 0.5rem; }
        @media(max-width: 640px) { .t-head,.t-row { grid-template-columns: 1fr; } .t-head { display: none; } .t-row { padding: 12px 1rem; } }
    </style>
</head>
<body>

<nav>
    <div class="nav-left">
        <div class="wm-icon">
            <div class="wm-sq"></div><div class="wm-sq"></div>
            <div class="wm-sq"></div><div class="wm-sq"></div>
        </div>
        <span class="brand-text">Restaurant admin</span>
    </div>
    <div class="nav-right">
        <a href="{{ route('admin.dashboard') }}" class="back-btn">← Back to Dashboard</a>
    </div>
</nav>

<div class="body">
    <div class="section-head">
        <div class="section-title">All Orders</div>
    </div>
    <div class="table">
        <div class="t-head">
            <span>#</span>
            <span>Order Details</span>
            <span>Restaurant</span>
            <span>Status</span>
            <span>Actions</span>
        </div>
        @forelse($orders as $order)
            <div class="t-row">
                <div class="order-id">#{{ $order->id }}</div>
                <div class="order-info">
                    <div>{{ $order->customer_name }}</div>
                    <div style="font-size:12px;color:#999;">{{ $order->customer_phone }}</div>
                    <div style="font-size:12px;color:#666;">Rs. {{ number_format($order->total, 0) }}</div>
                </div>
                <div class="restaurant-name">{{ $order->restaurant->name }}</div>
                <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span>
                <a href="#" class="view-btn">View Details</a>
            </div>
        @empty
            <div class="empty-state">
                <p>No orders yet</p>
            </div>
        @endforelse
    </div>
</div>

</body>
</html>
                <div class="order-info">
                    <div class="order-id">Order #{{ $order->id }}</div>
                    <div class="order-meta">
                        {{ $order->restaurant->name }} • {{ $order->customer_name }} • {{ $order->customer_phone }} • ${{ number_format($order->total, 2) }} • {{ $order->created_at->format('M j, Y g:i A') }}
                    </div>
                </div>
                <div class="order-actions">
                    <span class="status status-{{ $order->status }}">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span>
                    <a href="#" class="btn btn-sm btn-outline">View Details</a>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p>No orders yet</p>
            </div>
        @endforelse
    </div>
</div>

</body>
</html>