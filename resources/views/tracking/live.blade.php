<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Order Status | {{ $order ? '#' . $order->id : 'Order Details' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen py-8 px-4 flex items-center justify-center">

@if(!$order)
    <div class="max-w-md w-full bg-white rounded-2xl p-8 text-center border border-slate-200 shadow-sm space-y-4">
        <div class="w-14 h-14 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center mx-auto text-2xl">
            📦
        </div>
        <h2 class="text-lg font-bold text-slate-900">Order Not Found</h2>
        <p class="text-sm text-slate-500">Please check your order details or contact the restaurant via WhatsApp.</p>
    </div>
@else
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="bg-slate-900 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold">{{ $order->restaurant->name ?? 'Restaurant' }}</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Order #{{ $order->id }}</p>
                </div>
                @php
                    $statusColor = match($order->status) {
                        'delivered' => 'bg-emerald-500 text-white',
                        'cancelled' => 'bg-rose-500 text-white',
                        'out_for_delivery' => 'bg-blue-500 text-white',
                        'preparing' => 'bg-amber-500 text-white',
                        default => 'bg-slate-700 text-slate-200'
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                </span>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-5">
            <!-- Customer & Delivery -->
            <div class="text-sm space-y-1">
                <p class="font-semibold text-slate-800">{{ $order->customer_name ?: 'Customer' }}</p>
                <p class="text-slate-500 text-xs">{{ $order->delivery_place_name ?: $order->masked_delivery_address }}</p>
                <p class="text-slate-400 text-xs">{{ $order->created_at->format('M d, Y • h:i A') }}</p>
            </div>

            <!-- Items -->
            <div class="border-t border-slate-100 pt-4 space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Items Ordered</p>
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between text-sm py-1">
                        <span class="text-slate-700">{{ $item->quantity }}x {{ $item->name }} {{ $item->size ? "({$item->size})" : '' }}</span>
                        <span class="font-medium text-slate-900">PKR {{ number_format($item->subtotal, 0) }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Bill Totals -->
            <div class="border-t border-slate-100 pt-4 space-y-1.5 text-sm">
                <div class="flex justify-between text-slate-500 text-xs">
                    <span>Subtotal</span>
                    <span>PKR {{ number_format($order->subtotal, 0) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 text-xs">
                    <span>Delivery Fee</span>
                    <span>PKR {{ number_format($order->delivery_charge, 0) }}</span>
                </div>
                <div class="flex justify-between font-bold text-slate-900 pt-1 text-base border-t border-slate-100">
                    <span>Total (COD)</span>
                    <span class="text-indigo-600">PKR {{ number_format($order->total, 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 p-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Thank you for ordering with {{ $order->restaurant->name ?? 'us' }}! ❤️
        </div>
    </div>
@endif

</body>
</html>