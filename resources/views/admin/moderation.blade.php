@extends('layouts.admin')
@section('title', 'Spam & Content Moderation')
@section('header_title', 'Spam & Content Moderation')
@section('header_subtitle', 'Global blacklist for abusive phone numbers and bad keyword filters across all restaurant bots')

@section('content')
<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 22px;">
    <!-- Global Phone Number Blacklist -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Global Phone Blacklist</h3>
                <p>Blacklisted numbers are blocked from placing orders on any restaurant bot</p>
            </div>
            <button class="btn btn-danger btn-sm" onclick="document.getElementById('blacklistModal').style.display='flex'">➕ Add to Blacklist</button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Phone Number</th>
                        <th>Reason</th>
                        <th>Date Added</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blacklist as $b)
                        <tr>
                            <td><code>{{ $b->phone_number }}</code></td>
                            <td>{{ $b->reason ?: 'Abuse / Spam' }}</td>
                            <td style="font-size: 11.5px; color: var(--text-secondary);">{{ $b->created_at->format('d M Y') }}</td>
                            <td style="text-align: right;">
                                <form method="POST" action="{{ route('admin.moderation.blacklist.delete', $b->id) }}" onsubmit="return confirm('Remove {{ $b->phone_number }} from blacklist?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm" style="color: #10b981;">Unblock ✓</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 20px;">No phone numbers currently blacklisted.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 14px;">
            {{ $blacklist->links() }}
        </div>
    </div>

    <!-- Banned Words Filter -->
    <div>
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Abuse & Bad Word Filter</h3>
                    <p>Comma-separated keywords filtered in incoming customer chats</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.moderation.filter-words.update') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Filtered Keywords (English & Urdu)</label>
                    <textarea name="moderation_banned_words" class="form-textarea" rows="8" placeholder="abuse, scam, fraud, fake, gali, badword">{{ $filterWords }}</textarea>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">Incoming messages containing these words will be flagged or prevented from spamming.</div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Save Keyword Filters</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Blacklist -->
<div id="blacklistModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--card-bg); border-radius:12px; padding:24px; width:400px; max-width:90%; border:1px solid var(--border-color);">
        <h3 style="margin-bottom:12px;">Add Phone to Blacklist</h3>
        <form method="POST" action="{{ route('admin.moderation.blacklist.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Customer Phone Number *</label>
                <input type="text" name="phone_number" class="form-input" required placeholder="e.g. 923001234567 or 03001234567">
            </div>
            <div class="form-group">
                <label class="form-label">Reason for Blacklisting</label>
                <textarea name="reason" class="form-textarea" rows="2" placeholder="e.g. Fake orders repeatedly / abusive language"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('blacklistModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-danger">Blacklist Number</button>
            </div>
        </form>
    </div>
</div>
@endsection
