@extends('layouts.admin')
@section('title', 'Settings')
@section('header_title', 'Platform Settings')
@section('header_subtitle', 'Configure master administrator credentials, pricing plans, and system parameters')

@section('content')

<div style="max-width: 800px;">
    <div class="panel-card" style="margin-bottom: 24px;">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Super Admin Security</h3>
                <p>Master password to log in to the Super Admin platform</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.update-settings') }}">
            @csrf

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Current Master Password
                </label>
                <input type="password" name="current_password" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;" placeholder="••••••••••••">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    New Master Password
                </label>
                <input type="password" name="new_password" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;" placeholder="Enter new password">
            </div>

            <button type="submit" class="btn btn-primary">
                💾 Update Security Settings
            </button>
        </form>
    </div>

    <!-- SAAS PRICING CONFIGURATION -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Default SaaS Package Pricing</h3>
                <p>Monthly subscription fees charged to restaurant clients</p>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Package Plan</th>
                    <th>Default Price (PKR/month)</th>
                    <th>Included Features</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge badge-gray">TRIAL</span></td>
                    <td><strong>Free (0 PKR)</strong></td>
                    <td>14-day free trial with standard bot features</td>
                </tr>
                <tr>
                    <td><span class="badge badge-blue">BASIC</span></td>
                    <td><strong>3,000 PKR</strong></td>
                    <td>Unlimited orders, menu sync, rider assignment</td>
                </tr>
                <tr>
                    <td><span class="badge badge-green">PRO</span></td>
                    <td><strong>7,000 PKR</strong></td>
                    <td>Everything in Basic + Google Sheets sync + Priority support</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
