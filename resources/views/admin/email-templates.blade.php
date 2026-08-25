@extends('layouts.admin')
@section('title', 'Transactional Email Templates')
@section('header_title', 'Transactional Email Templates')
@section('header_subtitle', 'Configure system email notifications sent to restaurant owners')

@section('content')
<div style="max-width: 850px;">
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Email Templates Editor</h3>
                <p>Customize email notifications for registration, invoices, and subscription renewals</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.email-templates.update') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">✉️ Welcome Email (New Restaurant Onboarding)</label>
                <textarea name="email_welcome_tpl" class="form-textarea" rows="4">{{ \App\Models\Setting::get('email_welcome_tpl', "Welcome to Restaurant Bot Platform!\n\nYour account has been approved. You can log into your management portal using your registered credentials.\n\nNext Step: Connect your WhatsApp number by scanning the QR code in your dashboard.") }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">🧾 Subscription Invoice Receipt Email</label>
                <textarea name="email_invoice_tpl" class="form-textarea" rows="4">{{ \App\Models\Setting::get('email_invoice_tpl', "Dear Partner,\n\nWe have received your payment for your restaurant bot plan. Your invoice is available in your dashboard.\n\nThank you for choosing our automated ordering solution!") }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">⚠️ Plan Expiration Reminder Email</label>
                <textarea name="email_expiry_tpl" class="form-textarea" rows="4">{{ \App\Models\Setting::get('email_expiry_tpl', "Attention Restaurant Owner,\n\nYour subscription plan will expire in 3 days. Please renew your subscription to prevent any interruption in receiving WhatsApp customer orders.") }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">Save Email Templates</button>
        </form>
    </div>
</div>
@endsection
