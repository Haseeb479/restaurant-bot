@extends('layouts.dashboard')
@section('title', 'Daily Closing & Cash Settlement • ' . ($r->name ?? 'Dashboard'))
@section('header_title', 'Daily Closing & Cash Settlement')
@section('header_subtitle', 'End-of-day register closing, COD cash reconciliation, and rider settlement breakdown')

@section('content')
<div class="dashboard-container">

    <!-- Top Action Bar -->
    <div class="panel-card" style="padding: 16px 22px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 13px; font-weight: 700; color: #64748b;">Closing Date:</span>
                <form method="GET" action="" style="display: inline-flex; align-items: center; gap: 8px;">
                    <input type="date" name="date" value="{{ $date }}" class="input-text" style="padding: 6px 12px; font-size: 13px; border-radius: 8px; font-weight: 700;" onchange="this.form.submit()">
                </form>
                <span class="sub-badge green" style="font-size: 11.5px;">● Closing Summary</span>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="{{ route('dashboard.print-daily-closing', [$r->id, 'date' => $date]) }}" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; background: #059669; border-color: #059669;">
                    <span>🖨️</span> Print 80mm Closing Slip
                </a>
                <a href="{{ route('dashboard.live-orders', $r->id) }}" class="btn btn-secondary" style="font-weight: 600;">
                    ← Back to Live Orders
                </a>
            </div>
        </div>
    </div>

    <!-- 1. KEY FINANCIAL SUMMARY CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        
        <!-- Total Gross Sales -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Delivered Net Revenue</span>
                <div class="metric-icon-box green">💰</div>
            </div>
            <div class="metric-value" style="color: #059669;">PKR {{ number_format($totalSales, 0) }}</div>
            <div class="metric-footer">
                <span class="sub-badge green">● {{ $deliveredOrders->count() }} delivered</span>
                <span>{{ $orders->count() }} total placed</span>
            </div>
        </div>

        <!-- Cash to Collect (COD) -->
        <div class="metric-card" style="border: 2px solid #bbf7d0; background: #f0fdf4;">
            <div class="metric-header">
                <span class="metric-title" style="color: #166534; font-weight: 800;">💵 Physical Cash to Collect (COD)</span>
                <div class="metric-icon-box green" style="background: #10b981; color: #fff;">💵</div>
            </div>
            <div class="metric-value" style="color: #047857; font-size: 28px;">PKR {{ number_format($codCollected, 0) }}</div>
            <div class="metric-footer">
                <span class="sub-badge green">Must match cashier drawer</span>
            </div>
        </div>

        <!-- Digital / Online Payments -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">📱 Digital / Online Payments</span>
                <div class="metric-icon-box blue">💳</div>
            </div>
            <div class="metric-value" style="color: #0284c7;">PKR {{ number_format($onlineCollected, 0) }}</div>
            <div class="metric-footer">
                <span class="sub-badge blue">JazzCash / EasyPaisa / Bank</span>
            </div>
        </div>

        <!-- Delivery Fees -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Delivery Charges Pool</span>
                <div class="metric-icon-box purple">🛵</div>
            </div>
            <div class="metric-value" style="color: #7c3aed;">PKR {{ number_format($deliveryFees, 0) }}</div>
            <div class="metric-footer">
                <span>Food Net: PKR {{ number_format($foodSales, 0) }}</span>
            </div>
        </div>

    </div>

    <!-- 2. RIDER-BY-RIDER CASH SETTLEMENT (CRITICAL FOR CASHIERS) -->
    <div class="panel-card" style="padding: 22px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🛵 Rider Cash Settlement Reconciliation</span>
                    <span class="sub-badge green" style="font-size: 11px;">Hand-Over Checklist</span>
                </h3>
                <p style="font-size: 12.5px; color: #64748b; margin: 4px 0 0 0;">
                    Verify that each delivery rider hands over the exact Cash on Delivery (COD) amount before concluding their shift.
                </p>
            </div>
        </div>

        @if(empty($riderSettlement))
            <div style="text-align: center; padding: 40px 10px; color: #94a3b8;">
                <div style="font-size: 32px; margin-bottom: 8px;">🛵</div>
                <p style="font-weight: 700; font-size: 14px;">No delivered orders recorded for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569;">RIDER NAME</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569;">PHONE</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569;">DELIVERED ORDERS</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569;">DIGITAL DELIVERIES</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #047857; background: #ecfdf5;">COD CASH TO COLLECT FROM RIDER</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569;">TOTAL VOLUME</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 800; color: #475569;">SETTLED?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riderSettlement as $rs)
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 14px 16px; font-weight: 700; color: #0f172a;">
                                    🛵 {{ $rs['rider_name'] }}
                                </td>
                                <td style="padding: 14px 16px; font-size: 12px; color: #64748b; font-family: monospace;">
                                    {{ $rs['rider_phone'] ?: '—' }}
                                </td>
                                <td style="padding: 14px 16px; font-weight: 700;">
                                    {{ $rs['total_orders'] }} orders
                                </td>
                                <td style="padding: 14px 16px; font-size: 12.5px; color: #64748b;">
                                    PKR {{ number_format($rs['online_orders'], 0) }}
                                </td>
                                <td style="padding: 14px 16px; font-weight: 800; font-size: 15px; color: #059669; background: #f0fdf4;">
                                    PKR {{ number_format($rs['cod_to_collect'], 0) }}
                                </td>
                                <td style="padding: 14px 16px; font-size: 13px; color: #0f172a; font-weight: 700;">
                                    PKR {{ number_format($rs['total_volume'], 0) }}
                                </td>
                                <td style="padding: 14px 16px;">
                                    <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px; font-weight: 700; color: #475569;">
                                        <input type="checkbox" style="width: 16px; height: 16px; accent-color: #059669;">
                                        <span>Cash Received</span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- 3. PAYMENT METHOD BREAKDOWN -->
    <div class="panel-card" style="padding: 22px;">
        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 16px 0;">
            💳 Payment Channels Breakdown
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700;">Cash on Delivery (COD)</div>
                <div style="font-size: 20px; font-weight: 800; color: #047857; margin-top: 4px;">
                    PKR {{ number_format($paymentBreakdown['cash_on_delivery'] ?? 0, 0) }}
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700;">JazzCash</div>
                <div style="font-size: 20px; font-weight: 800; color: #dc2626; margin-top: 4px;">
                    PKR {{ number_format($paymentBreakdown['jazzcash'] ?? 0, 0) }}
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700;">EasyPaisa</div>
                <div style="font-size: 20px; font-weight: 800; color: #16a34a; margin-top: 4px;">
                    PKR {{ number_format($paymentBreakdown['easypaisa'] ?? 0, 0) }}
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">
                <div style="font-size: 12px; color: #64748b; font-weight: 700;">Direct Bank Transfer</div>
                <div style="font-size: 20px; font-weight: 800; color: #2563eb; margin-top: 4px;">
                    PKR {{ number_format($paymentBreakdown['bank_transfer'] ?? 0, 0) }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
