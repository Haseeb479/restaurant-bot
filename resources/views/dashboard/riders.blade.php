@extends('layouts.dashboard')
@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 700; color: #111; letter-spacing: -0.02em;">🛵 Rider Management</h1>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Manage delivery riders. Assigned riders receive automated notifications and their contact info is shared with customers.</p>
    </div>

    <button onclick="document.getElementById('modal-add-rider').style.display='flex'" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px; font-weight: 600; padding: 10px 16px; border-radius: 10px; background: #0e0e10; color: #fff; border: none; cursor: pointer;">
        <span>➕</span> Add New Rider
    </button>
</div>

<!-- Riders List Card -->
<div class="card" style="background: #fff; border: 1px solid #e8e8e4; border-radius: 16px; overflow: hidden;">
    <div style="padding: 16px 20px; border-bottom: 1px solid #f0efe9; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 14px; font-weight: 700; color: #111;">Active Delivery Riders ({{ $riders->count() }})</h3>
        <span style="font-size: 12px; color: #94a3b8;">Used when marking orders as "Dispatched"</span>
    </div>

    @if($riders->count() === 0)
        <div style="padding: 50px 20px; text-align: center; color: #94a3b8;">
            <div style="font-size: 40px; margin-bottom: 10px;">🛵</div>
            <h4 style="font-size: 16px; font-weight: 600; color: #334155; margin-bottom: 4px;">No riders added yet</h4>
            <p style="font-size: 13px; max-width: 380px; margin: 0 auto 16px;">Add your delivery riders here so you can quickly assign them to orders when dispatching.</p>
            <button onclick="document.getElementById('modal-add-rider').style.display='flex'" class="btn" style="background: #0e0e10; color: #fff; border-radius: 8px; padding: 8px 16px; font-size: 12px; cursor: pointer; border: none;">
                ➕ Add First Rider
            </button>
        </div>
    @else
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #fafaf9; border-bottom: 1px solid #f0efe9;">
                        <th style="padding: 12px 20px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Rider Name</th>
                        <th style="padding: 12px 20px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Phone Number</th>
                        <th style="padding: 12px 20px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 20px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Added On</th>
                        <th style="padding: 12px 20px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riders as $rider)
                    <tr style="border-bottom: 1px solid #f5f5f2;">
                        <td style="padding: 14px 20px; font-size: 13px; font-weight: 600; color: #111;">
                            🛵 {{ $rider->name }}
                        </td>
                        <td style="padding: 14px 20px; font-size: 13px; color: #475569;">
                            <a href="tel:{{ $rider->phone }}" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                {{ $rider->phone }}
                            </a>
                        </td>
                        <td style="padding: 14px 20px;">
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; background: {{ $rider->is_active ? '#dcfce7' : '#f1f5f9' }}; color: {{ $rider->is_active ? '#166534' : '#64748b' }};">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                {{ $rider->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="padding: 14px 20px; font-size: 12px; color: #94a3b8;">
                            {{ $rider->created_at ? $rider->created_at->format('d M Y') : '—' }}
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                <form method="POST" action="{{ route('dashboard.toggle-rider', [$restaurant->id, $rider->id]) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn" style="font-size: 11px; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; color: #475569;">
                                        {{ $rider->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('dashboard.delete-rider', [$restaurant->id, $rider->id]) }}" style="display: inline;" onsubmit="return confirm('Remove rider {{ $rider->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn" style="font-size: 11px; padding: 4px 8px; border-radius: 6px; border: 1px solid #fecaca; background: #fef2f2; cursor: pointer; color: #dc2626;">
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

<!-- Modal: Add Rider -->
<div id="modal-add-rider" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #fff; border-radius: 16px; max-width: 440px; width: 100%; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a;">➕ Add New Delivery Rider</h3>
            <button onclick="document.getElementById('modal-add-rider').style.display='none'" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer;">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.store-rider', $restaurant->id) }}">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Rider Full Name *</label>
                <input type="text" name="name" required placeholder="e.g. Ali Khan" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">WhatsApp / Mobile Phone *</label>
                <input type="text" name="phone" required placeholder="e.g. 03001234567" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; box-sizing: border-box;">
                <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 3px;">This phone number will be shared with the customer upon dispatch.</span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="document.getElementById('modal-add-rider').style.display='none'" class="btn" style="padding: 8px 16px; border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn" style="padding: 8px 20px; background: #0e0e10; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;">Save Rider</button>
            </div>
        </form>
    </div>
</div>

@endsection
