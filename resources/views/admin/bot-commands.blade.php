@extends('layouts.admin')
@section('title', 'Bot Command Triggers')
@section('header_title', 'Bot Commands & Keywords')
@section('header_subtitle', 'Configure keywords that trigger specific bot behaviors in chats')

@section('content')
<div style="max-width: 800px;">
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Recognized Intent Keywords</h3>
                <p>Comma-separated list of words in English and Roman Urdu that automatically route customer intent</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.bot-commands.update') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">📖 View Menu Triggers</label>
                <input type="text" name="bot_cmd_menu" class="form-input" value="{{ \App\Models\Setting::get('bot_cmd_menu', 'menu, items, khana, card, list, food') }}">
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Triggers the interactive menu catalog response.</div>
            </div>

            <div class="form-group">
                <label class="form-label">📍 Track Order Triggers</label>
                <input type="text" name="bot_cmd_track" class="form-input" value="{{ \App\Models\Setting::get('bot_cmd_track', 'track, status, order, kahan, rider, delivery') }}">
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Asks for tracking code or shows customer's latest order status.</div>
            </div>

            <div class="form-group">
                <label class="form-label">🏷️ Special Deals & Discounts Triggers</label>
                <input type="text" name="bot_cmd_deals" class="form-input" value="{{ \App\Models\Setting::get('bot_cmd_deals', 'deals, offers, discount, bachat, package') }}">
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Displays running promotional deals and combo savings.</div>
            </div>

            <div class="form-group">
                <label class="form-label">👨‍💼 Human Agent Request Triggers</label>
                <input type="text" name="bot_cmd_human" class="form-input" value="{{ \App\Models\Setting::get('bot_cmd_human', 'agent, human, help, madad, talk, call, owner') }}">
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Pauses bot automation and flags chat for live restaurant operator takeover.</div>
            </div>

            <div class="form-group">
                <label class="form-label">🛑 Cancel Order Request Triggers</label>
                <input type="text" name="bot_cmd_cancel" class="form-input" value="{{ \App\Models\Setting::get('bot_cmd_cancel', 'cancel, stop, khatam, nahi chahiye') }}">
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Initiates cancellation flow if order is not yet dispatched.</div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">Save Commands</button>
        </form>
    </div>
</div>
@endsection
