@extends('layouts.admin')
@section('title', 'Platform Policies & Terms')
@section('header_title', 'Platform Policies & Terms')
@section('header_subtitle', 'Edit Terms of Service, Privacy Notice, and Bot Acceptable Use Policies')

@section('content')
<div style="max-width: 850px;">
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Legal & Operational Guidelines</h3>
                <p>Platform terms displayed on onboarding and customer checkout portal</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.policies.update') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">📜 Terms of Service</label>
                <textarea name="policy_terms" class="form-textarea" rows="7">{{ $terms }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">🔒 Privacy Policy & Data Usage Notice</label>
                <textarea name="policy_privacy" class="form-textarea" rows="7">{{ $privacy }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">Save Policies</button>
        </form>
    </div>
</div>
@endsection
