@extends('layouts.admin')
@section('title', 'Message Templates')
@section('header_title', 'Bot Message Templates')
@section('header_subtitle', 'Configure pre-built transactional messages and customer notifications')

@section('content')
<div style="max-width: 850px;">
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Default Bot Message Templates</h3>
                <p>Use variables: <code>{restaurant_name}</code>, <code>{customer_name}</code>, <code>{tracking_code}</code>, <code>{order_total}</code>, <code>{tracking_link}</code></p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.bot-templates.update') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">👋 Welcome / Greeting Template</label>
                <textarea name="template_greeting" class="form-textarea" rows="2">{{ \App\Models\Setting::get('template_greeting', 'Welcome to {restaurant_name}! 🍽️ Type *menu* to see our dishes or tell me what you would like to order today.') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">✅ Order Confirmed Template</label>
                <textarea name="template_order_confirmed" class="form-textarea" rows="3">{{ \App\Models\Setting::get('template_order_confirmed', "🎉 Thank you {customer_name}! Your order #{tracking_code} has been confirmed.\nTotal Amount: Rs. {order_total}\nLive Tracking: {tracking_link}") }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">🛵 Rider Dispatched Template</label>
                <textarea name="template_rider_dispatched" class="form-textarea" rows="2">{{ \App\Models\Setting::get('template_rider_dispatched', '🛵 Good news! Your order #{tracking_code} has been picked up by our rider and is on its way!') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">❌ Out of Stock Notice</label>
                <textarea name="template_out_of_stock" class="form-textarea" rows="2">{{ \App\Models\Setting::get('template_out_of_stock', 'Sorry, {item_name} is currently out of stock. Would you like to check out our other delicious options?') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">🌙 Restaurant Closed Template</label>
                <textarea name="template_closed" class="form-textarea" rows="2">{{ \App\Models\Setting::get('template_closed', '{restaurant_name} is currently closed. Our kitchen opens at {opening_time}. We look forward to serving you soon!') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">👨‍💼 Human Agent Handover Notice</label>
                <textarea name="template_human_handover" class="form-textarea" rows="2">{{ \App\Models\Setting::get('template_human_handover', "I've notified a human team member from {restaurant_name}. Someone will reply to your message shortly!") }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">📢 Deal Broadcast Default Format</label>
                <textarea name="template_deal_broadcast" class="form-textarea" rows="2">{{ \App\Models\Setting::get('template_deal_broadcast', "🔥 Exclusive Offer from {restaurant_name}!\n{deal_details}\nOrder now by replying to this chat!") }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">Save Templates</button>
        </form>
    </div>
</div>
@endsection
