@extends('layouts.admin')
@section('title', 'Platform Announcements')
@section('header_title', 'Broadcast Announcements')
@section('header_subtitle', 'Publish platform updates, maintenance alerts, and banners to restaurant owner panels')

@section('content')
<div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 22px;">
    <!-- Active Announcements List -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Broadcast Messages</h3>
                <p>Announcements currently or previously displayed to restaurant owners</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            @forelse($announcements as $a)
                <div style="padding: 14px 16px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-page);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if($a->type === 'maintenance')
                                <span class="badge badge-yellow">🛠️ Maintenance</span>
                            @elseif($a->type === 'warning')
                                <span class="badge badge-red">⚠️ Warning</span>
                            @elseif($a->type === 'success')
                                <span class="badge badge-green">🎉 Update</span>
                            @else
                                <span class="badge badge-blue">ℹ️ Notice</span>
                            @endif
                            <h4 style="font-size: 13.5px; font-weight: 800;">{{ $a->title }}</h4>
                        </div>
                        <form method="POST" action="{{ route('admin.announcements.delete', $a->id) }}" onsubmit="return confirm('Delete this announcement?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>

                    <div style="font-size: 12.5px; color: var(--text-primary); line-height: 1.5; margin-bottom: 8px;">
                        {{ $a->content }}
                    </div>

                    <div style="font-size: 11px; color: var(--text-secondary); display: flex; gap: 12px;">
                        <span>Target: <strong>{{ strtoupper($a->target) }}</strong></span>
                        <span>Posted: {{ $a->created_at->diffForHumans() }}</span>
                        @if($a->expires_at)
                            <span>Expires: {{ $a->expires_at->format('d M Y') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-secondary); padding: 25px;">
                    No broadcast announcements created yet.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 14px;">
            {{ $announcements->links() }}
        </div>
    </div>

    <!-- Create Announcement Form -->
    <div>
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Publish New Notice</h3>
                    <p>Send a banner to tenant dashboards</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Announcement Title *</label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. Scheduled Bot Server Maintenance Tonight" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Notice Type *</label>
                    <select name="type" class="form-select" required>
                        <option value="info">Information (Blue)</option>
                        <option value="success">Feature Release / Success (Green)</option>
                        <option value="warning">Important Alert (Orange)</option>
                        <option value="maintenance">Server Maintenance (Yellow)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notice Content *</label>
                    <textarea name="content" class="form-textarea" rows="4" placeholder="Type announcement details clearly..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Expiry Date (Optional)</label>
                    <input type="date" name="expires_at" class="form-input">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">📢 Broadcast Announcement</button>
            </form>
        </div>
    </div>
</div>
@endsection
