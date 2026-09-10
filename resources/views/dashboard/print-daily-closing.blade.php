<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Closing — {{ $r->name }} ({{ \Carbon\Carbon::parse($date)->format('d M Y') }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Mono', monospace, sans-serif;
            background-color: #f1f5f9;
            color: #000;
            display: flex;
            justify-content: center;
            padding: 30px 15px;
            font-size: 12px;
            line-height: 1.4;
        }
        .no-print-bar {
            position: fixed;
            top: 15px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }
        .btn {
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-close { background: #fff; color: #334155; }

        .receipt-container {
            width: 80mm;
            background: #ffffff;
            padding: 16px 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 6px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .double-divider {
            border-top: 2px solid #000;
            margin: 8px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .bold { font-weight: 700; }
        .header-title { font-size: 15px; font-weight: 800; letter-spacing: -0.5px; }
        .header-sub { font-size: 10.5px; color: #333; margin-top: 2px; }

        @media print {
            body { background: transparent; padding: 0; }
            .no-print-bar { display: none; }
            .receipt-container {
                width: 100%;
                box-shadow: none;
                padding: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <button class="btn btn-print" onclick="window.print()">🖨️ Print Slip</button>
    <button class="btn btn-close" onclick="window.close()">✕ Close</button>
</div>

<div class="receipt-container">
    <div class="text-center">
        <div class="header-title">{{ strtoupper($r->name) }}</div>
        <div class="header-sub">DAILY CLOSING & CASH REGISTER</div>
        <div class="header-sub">Date: {{ \Carbon\Carbon::parse($date)->format('d-M-Y') }} | Generated: {{ now()->format('h:i A') }}</div>
    </div>

    <div class="double-divider"></div>

    <div class="row">
        <span>Total Orders:</span>
        <span class="bold">{{ $orders->count() }}</span>
    </div>
    <div class="row">
        <span>Delivered / Completed:</span>
        <span class="bold">{{ $deliveredOrders->count() }}</span>
    </div>
    <div class="row">
        <span>Cancelled Orders:</span>
        <span class="bold">{{ $cancelledOrders->count() }}</span>
    </div>

    <div class="divider"></div>

    <div class="row bold" style="font-size: 13px;">
        <span>NET SALES REVENUE:</span>
        <span>PKR {{ number_format($totalSales, 0) }}</span>
    </div>

    <div class="divider"></div>

    <div class="text-center bold" style="font-size: 11px; margin: 4px 0;">PAYMENT BREAKDOWN</div>
    <div class="row">
        <span>Cash on Delivery (COD):</span>
        <span class="bold">PKR {{ number_format($codCollected, 0) }}</span>
    </div>
    <div class="row">
        <span>Digital / Online:</span>
        <span class="bold">PKR {{ number_format($onlineCollected, 0) }}</span>
    </div>

    <div class="double-divider"></div>

    <!-- PHYSICAL CASH BOX RECONCILIATION -->
    <div class="text-center bold" style="font-size: 12px; margin: 4px 0;">
        *** CASH DRAWER AUDIT ***
    </div>
    <div class="row bold" style="font-size: 13.5px; padding: 4px 0;">
        <span>TOTAL COD CASH:</span>
        <span>PKR {{ number_format($codCollected, 0) }}</span>
    </div>
    <div style="font-size: 9.5px; text-align: center; color: #555; margin-bottom: 6px;">
        (Cashier physical count must match this figure)
    </div>

    <div class="divider"></div>

    <!-- RIDER RECONCILIATION -->
    <div class="text-center bold" style="font-size: 11px; margin: 4px 0;">RIDER SETTLEMENT</div>
    @foreach($riderSettlement as $rs)
        <div class="row">
            <span>{{ $rs['rider_name'] }} ({{ $rs['orders_count'] }} ord):</span>
            <span class="bold">PKR {{ number_format($rs['cod_to_collect'], 0) }}</span>
        </div>
    @endforeach

    <div class="double-divider"></div>

    <div style="margin-top: 24px;">
        <div class="row" style="margin-bottom: 20px;">
            <span>Cashier Sign: _______________</span>
        </div>
        <div class="row">
            <span>Manager Sign: _______________</span>
        </div>
    </div>

    <div class="divider"></div>
    <div class="text-center" style="font-size: 9.5px; color: #555; margin-top: 6px;">
        Powered by Foodio WhatsApp Restaurant Bot
    </div>
</div>

<script>
    // Auto-trigger print dialog after small render delay
    window.addEventListener('load', () => {
        setTimeout(() => window.print(), 350);
    });
</script>

</body>
</html>
