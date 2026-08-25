@extends('layouts.admin')
@section('title', 'Billing, Plans & Payments')
@section('header_title', 'Billing, Subscriptions & Payment Methods')
@section('header_subtitle', 'Manage subscription tiers, local Pakistani payment accounts (JazzCash, EasyPaisa, Bank Transfer), and invoices')

@section('content')
<!-- Financial Highlights -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Total Collected</span>
            <div class="metric-icon green">💰</div>
        </div>
        <div class="metric-value">Rs. {{ number_format($totalInvoiced) }}</div>
        <div class="metric-footer">Paid invoice receipts</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Unpaid / Overdue</span>
            <div class="metric-icon orange">⏳</div>
        </div>
        <div class="metric-value">Rs. {{ number_format($unpaidInvoices) }}</div>
        <div class="metric-footer">Pending tenant subscriptions</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Active Plans</span>
            <div class="metric-icon blue">🏷️</div>
        </div>
        <div class="metric-value">{{ $plans->count() }} Tiers</div>
        <div class="metric-footer">Starter, Pro, Enterprise packages</div>
    </div>
</div>

<!-- 1. Subscription Tiers / Pricing Plans -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>1. Subscription Pricing Plans</h3>
            <p>Manage SaaS subscription packages, monthly/yearly pricing, and order limits</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openCreatePlanModal()">➕ Create Plan</button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
        @foreach($plans as $plan)
            <div style="background: var(--bg-page); border: 2px solid {{ $plan->is_popular ? '#4f46e5' : 'var(--border-color)' }}; border-radius: 12px; padding: 18px; position: relative;">
                @if($plan->is_popular)
                    <span style="position: absolute; top: -10px; right: 14px; background: #4f46e5; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 99px; text-transform: uppercase;">Most Popular</span>
                @endif
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="font-size: 15px; font-weight: 800;">{{ $plan->name }}</h4>
                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">Slug: <code>{{ $plan->slug }}</code></div>
                    </div>
                </div>

                <div style="margin: 14px 0 10px;">
                    <span style="font-size: 22px; font-weight: 800; color: #10b981;">Rs. {{ number_format($plan->price_monthly) }}</span>
                    <span style="font-size: 11.5px; color: var(--text-secondary);">/ month</span>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">Yearly: Rs. {{ number_format($plan->price_yearly) }}</div>
                </div>

                <ul style="list-style: none; font-size: 12px; line-height: 1.8; margin-bottom: 16px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                    <li>✓ <strong>{{ number_format($plan->max_orders_per_month) }}</strong> orders/month</li>
                    <li>✓ <strong>{{ number_format($plan->max_menu_items) }}</strong> menu dishes limit</li>
                    <li>✓ WhatsApp Bot Assistant</li>
                    @if(!empty($plan->features['ai_suggestions']))
                        <li>✓ AI Smart Upselling & Recommendations</li>
                    @endif
                    @if(!empty($plan->features['deal_broadcast']))
                        <li>✓ Customer Broadcast Deals</li>
                    @endif
                </ul>

                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center;" onclick="openEditPlanModal('{{ $plan->id }}', '{{ addslashes($plan->name) }}', '{{ $plan->price_monthly }}', '{{ $plan->price_yearly }}', '{{ $plan->max_orders_per_month }}', '{{ $plan->max_menu_items }}', {{ $plan->is_popular ? 1 : 0 }}, {{ $plan->is_active ? 1 : 0 }})">Edit Tier</button>
                    <form method="POST" action="{{ route('admin.billing.plans.delete', $plan->id) }}" onsubmit="return confirm('Delete this plan?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- 2. Pakistani Manual Payment Methods Setup (JazzCash, EasyPaisa, Bank Transfer) -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>2. Local Pakistani Payment Accounts (Free Gateway Alternative)</h3>
            <p>Configure JazzCash, EasyPaisa, and Bank Transfer account info shown to restaurant owners on checkout</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.billing.payment-methods.update') }}">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
            <!-- JazzCash -->
            <div style="background: var(--bg-page); padding: 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 800; color: #dc2626; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                    <span>📱 JazzCash Account</span>
                </h4>
                <div class="form-group">
                    <label class="form-label">Account Title</label>
                    <input type="text" name="payment_jazzcash_title" class="form-input" value="{{ \App\Models\Setting::get('payment_jazzcash_title', '') }}" placeholder="e.g. Haseeb Tech Services">
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="payment_jazzcash_number" class="form-input" value="{{ \App\Models\Setting::get('payment_jazzcash_number', '') }}" placeholder="e.g. 03001234567">
                </div>
            </div>

            <!-- EasyPaisa -->
            <div style="background: var(--bg-page); padding: 14px; border-radius: 10px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 800; color: #16a34a; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                    <span>📲 EasyPaisa Account</span>
                </h4>
                <div class="form-group">
                    <label class="form-label">Account Title</label>
                    <input type="text" name="payment_easypaisa_title" class="form-input" value="{{ \App\Models\Setting::get('payment_easypaisa_title', '') }}" placeholder="e.g. Haseeb Tech Services">
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="payment_easypaisa_number" class="form-input" value="{{ \App\Models\Setting::get('payment_easypaisa_number', '') }}" placeholder="e.g. 03451234567">
                </div>
            </div>
        </div>

        <!-- Bank Transfer -->
        <div style="background: var(--bg-page); padding: 14px; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 16px;">
            <h4 style="font-size: 13px; font-weight: 800; color: #2563eb; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <span>🏦 Bank Transfer / Wire</span>
            </h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="payment_bank_name" class="form-input" value="{{ \App\Models\Setting::get('payment_bank_name', '') }}" placeholder="e.g. Meezan Bank / HBL / Faysal Bank">
                </div>
                <div class="form-group">
                    <label class="form-label">Account Title</label>
                    <input type="text" name="payment_bank_title" class="form-input" value="{{ \App\Models\Setting::get('payment_bank_title', '') }}" placeholder="e.g. Restaurant Bot PVT">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="payment_bank_account" class="form-input" value="{{ \App\Models\Setting::get('payment_bank_account', '') }}" placeholder="e.g. 01020304050607">
                </div>
                <div class="form-group">
                    <label class="form-label">IBAN (24 Digits)</label>
                    <input type="text" name="payment_bank_iban" class="form-input" value="{{ \App\Models\Setting::get('payment_bank_iban', '') }}" placeholder="e.g. PK00MEZN0001020304050607">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Payment Instructions for Restaurant Owners</label>
            <textarea name="payment_instructions" class="form-textarea" rows="2" placeholder="Send screenshot of receipt on WhatsApp with restaurant name to activate instantly...">{{ \App\Models\Setting::get('payment_instructions', '') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 9px 20px;">Save Payment Accounts</button>
    </form>
</div>

<!-- 3. Invoices Ledger -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>3. Invoices & Billing Ledger</h3>
            <p>Track payments, receipts, generate manual invoices</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" onclick="openCreateInvoiceModal()">➕ Generate Invoice</button>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Restaurant</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td><code>{{ $inv->invoice_number }}</code></td>
                        <td><strong>{{ $inv->restaurant->name ?? 'N/A' }}</strong></td>
                        <td>{{ $inv->plan_name }}</td>
                        <td style="font-weight: 700;">Rs. {{ number_format($inv->amount) }}</td>
                        <td><span class="badge badge-gray">{{ $inv->payment_method }}</span></td>
                        <td>
                            @if($inv->status === 'paid')
                                <span class="badge badge-green">Paid</span>
                            @elseif($inv->status === 'unpaid')
                                <span class="badge badge-yellow">Unpaid</span>
                            @else
                                <span class="badge badge-red">{{ ucfirst($inv->status) }}</span>
                            @endif
                        </td>
                        <td style="font-size: 11.5px; color: var(--text-secondary);">{{ $inv->created_at->format('d M Y') }}</td>
                        <td style="text-align: right;">
                            <form method="POST" action="{{ route('admin.billing.invoices.status', $inv->id) }}" style="display:inline;">
                                @csrf
                                @if($inv->status !== 'paid')
                                    <input type="hidden" name="status" value="paid">
                                    <button type="submit" class="btn btn-success btn-sm">Mark Paid ✓</button>
                                @else
                                    <input type="hidden" name="status" value="unpaid">
                                    <button type="submit" class="btn btn-secondary btn-sm">Mark Unpaid</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 20px;">No invoices generated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 14px;">
        {{ $invoices->links() }}
    </div>
</div>

<!-- Modal: Create Plan -->
<div id="planModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--card-bg); border-radius:12px; padding:24px; width:480px; max-width:90%; border:1px solid var(--border-color);">
        <h3 id="planModalTitle" style="margin-bottom:12px;">Create Subscription Plan</h3>
        <form id="planForm" method="POST" action="{{ route('admin.billing.plans.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Plan Name *</label>
                <input type="text" id="p_name" name="name" class="form-input" required placeholder="e.g. Pro Business">
            </div>
            <div class="form-group" id="p_slug_group">
                <label class="form-label">Slug (Identifier) *</label>
                <input type="text" id="p_slug" name="slug" class="form-input" required placeholder="e.g. pro-business">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Monthly Price (PKR) *</label>
                    <input type="number" id="p_price_m" name="price_monthly" class="form-input" required value="3000">
                </div>
                <div class="form-group">
                    <label class="form-label">Yearly Price (PKR) *</label>
                    <input type="number" id="p_price_y" name="price_yearly" class="form-input" required value="30000">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Max Orders / Month *</label>
                    <input type="number" id="p_orders" name="max_orders_per_month" class="form-input" required value="1000">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Dishes Limit *</label>
                    <input type="number" id="p_items" name="max_menu_items" class="form-input" required value="100">
                </div>
            </div>
            <div style="margin: 10px 0 16px; display: flex; gap: 14px;">
                <label style="font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <input type="checkbox" id="p_popular" name="is_popular" value="1">
                    <span>Mark as Popular</span>
                </label>
                <label style="font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <input type="checkbox" id="p_active" name="is_active" value="1" checked>
                    <span>Active</span>
                </label>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="closePlanModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Generate Invoice -->
<div id="invoiceModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--card-bg); border-radius:12px; padding:24px; width:460px; max-width:90%; border:1px solid var(--border-color);">
        <h3 style="margin-bottom:12px;">Generate Subscription Invoice</h3>
        <form method="POST" action="{{ route('admin.billing.invoices.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Restaurant *</label>
                <select name="restaurant_id" class="form-select" required>
                    @foreach($restaurants as $r)
                        <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->city ?: 'N/A' }})</option>
                    @endforeach
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Plan Name *</label>
                    <input type="text" name="plan_name" class="form-input" value="Pro Business" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (PKR) *</label>
                    <input type="number" name="amount" class="form-input" value="3500" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="JazzCash">JazzCash</option>
                        <option value="EasyPaisa">EasyPaisa</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Initial Status</label>
                    <select name="status" class="form-select">
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Transaction Reference (Optional)</label>
                <input type="text" name="payment_reference" class="form-input" placeholder="e.g. TID-9821839218">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="closeInvoiceModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreatePlanModal() {
    document.getElementById('planModalTitle').textContent = 'Create Subscription Plan';
    document.getElementById('planForm').action = '{{ route("admin.billing.plans.store") }}';
    document.getElementById('p_slug_group').style.display = 'block';
    document.getElementById('p_name').value = '';
    document.getElementById('p_slug').value = '';
    document.getElementById('planModal').style.display = 'flex';
}
function openEditPlanModal(id, name, priceM, priceY, orders, items, popular, active) {
    document.getElementById('planModalTitle').textContent = 'Edit ' + name;
    document.getElementById('planForm').action = '/admin/billing/plans/' + id + '/update';
    document.getElementById('p_slug_group').style.display = 'none';
    document.getElementById('p_name').value = name;
    document.getElementById('p_price_m').value = priceM;
    document.getElementById('p_price_y').value = priceY;
    document.getElementById('p_orders').value = orders;
    document.getElementById('p_items').value = items;
    document.getElementById('p_popular').checked = !!popular;
    document.getElementById('p_active').checked = !!active;
    document.getElementById('planModal').style.display = 'flex';
}
function closePlanModal() {
    document.getElementById('planModal').style.display = 'none';
}
function openCreateInvoiceModal() {
    document.getElementById('invoiceModal').style.display = 'flex';
}
function closeInvoiceModal() {
    document.getElementById('invoiceModal').style.display = 'none';
}
</script>
@endsection
