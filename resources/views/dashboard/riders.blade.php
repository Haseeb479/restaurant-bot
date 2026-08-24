@extends('layouts.dashboard')
@section('title', 'Riders')
@section('header_title', 'Delivery Riders Management')
@section('header_subtitle', 'Manage delivery personnel, contact details, and active status')

@section('content')

<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Active Delivery Team ({{ $riders->count() }})</h3>
            <p>Assigned to orders when marking as "Dispatched" to send live customer updates</p>
        </div>

        <button onclick="document.getElementById('modal-add-rider').style.display='flex'" class="btn btn-primary">
            + Add New Rider
        </button>
    </div>

    @if($riders->count() === 0)
        <div style="padding: 50px 20px; text-align: center; color: #94a3b8;">
            <div style="font-size: 40px; margin-bottom: 10px;">🛵</div>
            <h4 style="font-size: 16px; font-weight: 700; color: #334155; margin-bottom: 4px;">No riders added yet</h4>
            <p style="font-size: 13px; max-width: 380px; margin: 0 auto 16px;">Add your delivery riders here so you can quickly assign them to orders when dispatching.</p>
            <button onclick="document.getElementById('modal-add-rider').style.display='flex'" class="btn btn-primary">
                + Add First Rider
            </button>
        </div>
    @else
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rider Name</th>
                        <th>Phone Number</th>
                        <th>Status</th>
                        <th>Added On</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riders as $rider)
                    <tr>
                        <td>
                            <strong style="font-size: 13px; color: #0f172a;">🛵 {{ $rider->name }}</strong>
                        </td>
                        <td>
                            <a href="tel:{{ $rider->phone }}" style="color: #4f46e5; text-decoration: none; font-weight: 600;">
                                {{ $rider->phone }}
                            </a>
                        </td>
                        <td>
                            <span class="badge-status {{ $rider->is_active ? 'delivered' : 'cancelled' }}">
                                ● {{ $rider->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 12px; color: #94a3b8;">{{ $rider->created_at->format('d M Y') }}</span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                <form method="POST" action="{{ route('dashboard.delete-rider', [$restaurant->id, $rider->id]) }}" onsubmit="return confirm('Delete rider {{ $rider->name }}?')" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="color: #dc2626; padding: 4px 10px; font-size: 11px;">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- ADD RIDER MODAL -->
<div id="modal-add-rider" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; border-radius: 20px; max-width: 460px; width: 100%; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a;">🛵 Add Delivery Rider</h3>
            <button onclick="document.getElementById('modal-add-rider').style.display='none'" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #94a3b8;">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.store-rider', $restaurant->id) }}">
            @csrf

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Rider Full Name <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" name="name" required placeholder="e.g. Hamza Mulla, Ali Raza" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Phone Number <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" name="phone" required placeholder="e.g. 03001234567" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                <span style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">This phone number is sent to customers via WhatsApp for tracking</span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('modal-add-rider').style.display='none'" class="btn btn-secondary" style="padding: 10px 18px;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                    ✓ Save Rider
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
