<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #{{ $order->tracking_code }} - {{ $restaurant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Space Mono', monospace, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            display: flex;
            justify-content: center;
            padding: 30px 15px;
            font-size: 13px;
            line-height: 1.4;
        }

        .no-print-bar {
            position: fixed;
            top: 15px;
            display: flex;
            gap: 12px;
            z-index: 100;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            border: none;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.15s ease;
        }

        .btn-print {
            background: #0f172a;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        .btn-close {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-close:hover {
            background: #f8fafc;
        }

        /* Printable Receipt Card (80mm standard width optimized) */
        .receipt-card {
            background: #ffffff;
            width: 380px;
            max-width: 100%;
            padding: 24px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            margin-top: 35px;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .receipt-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .receipt-header p {
            font-size: 11.5px;
            color: #475569;
            line-height: 1.35;
        }

        .receipt-type-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 10px;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .meta-section {
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 12px;
            margin-bottom: 12px;
            font-size: 12px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .meta-lbl {
            color: #64748b;
        }

        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }

        .parcel-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 12px;
        }

        .parcel-box-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .parcel-addr {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            margin-top: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 12px;
        }

        .items-table th {
            text-align: left;
            padding: 6px 0;
            border-bottom: 1.5px solid #0f172a;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table th.tar, .items-table td.tar {
            text-align: right;
        }

        .items-table td {
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            vertical-align: top;
        }

        .item-name {
            font-weight: 700;
            color: #0f172a;
        }

        .item-meta {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 1px;
        }

        .totals-section {
            border-top: 1.5px solid #0f172a;
            padding-top: 10px;
            margin-bottom: 14px;
            font-size: 12px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .grand-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px dashed #cbd5e1;
            padding-top: 8px;
            margin-top: 6px;
        }

        .receipt-footer {
            text-align: center;
            border-top: 2px dashed #cbd5e1;
            padding-top: 14px;
            margin-top: 14px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }

        .tracking-link-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 10.5px;
            margin: 8px 0;
            word-break: break-all;
            color: #334155;
            font-weight: 700;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .receipt-card {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin-top: 0 !important;
                border-radius: 0 !important;
            }

            @page {
                margin: 6mm;
            }
        }
    </style>
</head>
<body>

    <!-- TOP ACTION BAR -->
    <div class="no-print-bar">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ Print Parcel Bill
        </button>
        <button onclick="window.close()" class="btn btn-close">
            ✕ Close
        </button>
    </div>

    <!-- THERMAL RECEIPT SLIP -->
    <div class="receipt-card">
        
        <!-- HEADER -->
        <div class="receipt-header">
            <h1>{{ $restaurant->name }}</h1>
            @if($restaurant->address || $restaurant->city)
                <p>{{ $restaurant->address }}{{ $restaurant->address && $restaurant->city ? ', ' : '' }}{{ $restaurant->city }}</p>
            @endif
            <p>WhatsApp / Phone: {{ $restaurant->whatsapp_number }}</p>
            <div class="receipt-type-badge">📦 PARCEL DELIVERY SLIP</div>
        </div>

        <!-- ORDER META -->
        <div class="meta-section">
            <div class="meta-row">
                <span class="meta-lbl">ORDER CODE:</span>
                <span class="meta-val" style="font-size: 14px; letter-spacing: 0.5px;">#{{ $order->tracking_code }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-lbl">DATE & TIME:</span>
                <span class="meta-val">{{ $order->created_at->format('d-M-Y h:i A') }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-lbl">PAYMENT:</span>
                <span class="meta-val">{{ strtoupper(str_replace('_', ' ', $order->payment_method ?: 'CASH ON DELIVERY')) }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-lbl">STATUS:</span>
                <span class="meta-val">{{ strtoupper($order->status) }}</span>
            </div>
        </div>

        <!-- PARCEL & CUSTOMER ATTACHMENT BOX -->
        <div class="parcel-box">
            <div class="parcel-box-title">📍 Customer & Delivery Details</div>
            <div style="font-weight: 700; font-size: 13px; color: #0f172a;">
                👤 {{ $order->customer_name ?: 'Valued Customer' }}
            </div>
            <div style="color: #475569; font-size: 12px; margin-top: 1px;">
                📞 {{ $order->formatted_customer_phone }}
            </div>
            <div class="parcel-addr">
                {{ $order->delivery_address ?: $order->masked_delivery_address }}
            </div>

            @if($order->rider_name)
                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 11.5px; color: #0f172a;">
                    🛵 <strong>Rider:</strong> {{ $order->rider_name }}
                    @if($order->rider_phone)
                        ({{ $order->rider_phone }})
                    @endif
                </div>
            @endif


        </div>

        <!-- ITEMS TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">ITEM</th>
                    <th class="tar" style="width: 15%;">QTY</th>
                    <th class="tar" style="width: 30%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->display_label }}</div>
                            @if($item->unit_price > 0)
                                <div class="item-meta">@ Rs. {{ number_format($item->unit_price, 0) }}</div>
                            @endif
                        </td>
                        <td class="tar" style="font-weight: 700;">x{{ $item->quantity }}</td>
                        <td class="tar" style="font-weight: 700;">Rs. {{ number_format($item->subtotal, 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #64748b; padding: 10px 0;">
                            Order confirmed via WhatsApp
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- TOTALS BREAKDOWN -->
        @php
            $foodSubtotal = $order->items->sum('subtotal') ?: $order->total;
            $deliveryFee  = $order->delivery_charge ?? 0;
            $grandTotal   = $foodSubtotal + $deliveryFee;
        @endphp
        <div class="totals-section">
            <div class="total-row">
                <span class="meta-lbl">Food Subtotal:</span>
                <span class="meta-val">Rs. {{ number_format($foodSubtotal, 0) }}</span>
            </div>
            @if($deliveryFee > 0)
                <div class="total-row">
                    <span class="meta-lbl">Delivery Fee:</span>
                    <span class="meta-val">Rs. {{ number_format($deliveryFee, 0) }}</span>
                </div>
            @endif
            <div class="grand-total-row">
                <span>TOTAL PAYABLE:</span>
                <span>Rs. {{ number_format($grandTotal, 0) }}</span>
            </div>
        </div>

        <!-- FOOTER & TRACKING -->
        <div class="receipt-footer">
            <p><strong>Track your parcel live:</strong></p>
            <div class="tracking-link-box">
                {{ url('/track/' . $order->tracking_code) }}
            </div>
            <p style="margin-top: 6px;">Thank you for ordering with {{ $restaurant->name }}!</p>
            <p style="font-size: 9.5px; color: #94a3b8; margin-top: 4px;">Powered by WhatsApp Restaurant Bot</p>
        </div>

    </div>

    <!-- Auto-Print on Load -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 400);
        });
    </script>

</body>
</html>
