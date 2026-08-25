@extends('layouts.admin')
@section('title', 'Ticket #' . $ticket->ticket_id)
@section('header_title', 'Ticket #' . $ticket->ticket_id)
@section('header_subtitle', $ticket->subject)

@section('content')
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <!-- Conversation Thread & Reply -->
    <div>
        <!-- Original Issue -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>{{ $ticket->subject }}</h3>
                    <p>Submitted by <strong>{{ $ticket->restaurant->name ?? $ticket->contact_name ?? 'User' }}</strong> ({{ $ticket->contact_phone }}) • {{ $ticket->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <a href="{{ route('admin.support') }}" class="btn btn-secondary btn-sm">← Back to Queue</a>
            </div>
            <div style="background: var(--bg-page); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 13px; line-height: 1.6;">
                {{ $ticket->description }}
            </div>
        </div>

        <!-- Messages Thread -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Conversation History</h3>
                    <p>Message thread between Super Admin and restaurant</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                @forelse($ticket->messages as $msg)
                    <div style="padding: 12px 14px; border-radius: 10px; {{ $msg->sender_type === 'admin' ? 'background: rgba(79, 70, 229, 0.08); border-left: 4px solid #4f46e5;' : 'background: var(--bg-page); border-left: 4px solid #10b981;' }}">
                        <div style="display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; margin-bottom: 4px;">
                            <span>{{ $msg->sender_name }} ({{ ucfirst($msg->sender_type) }})</span>
                            <span style="color: var(--text-secondary); font-weight: 400;">{{ $msg->created_at->diffForHumans() }}</span>
                        </div>
                        <div style="font-size: 12.5px;">{{ $msg->message }}</div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--text-secondary); padding: 15px;">No reply messages yet.</div>
                @endforelse
            </div>

            <!-- Send Reply Form -->
            <form method="POST" action="{{ route('admin.support.reply', $ticket->id) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Post Admin Reply</label>
                    <textarea name="message" class="form-textarea" rows="3" required placeholder="Type your response to the restaurant owner here..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Response</button>
            </form>
        </div>
    </div>

    <!-- Ticket Controls Sidebar -->
    <div>
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Ticket Properties</h3>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.support.status', $ticket->id) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <input type="text" class="form-input" value="{{ ucfirst($ticket->priority) }}" readonly style="opacity: 0.8;">
                </div>

                <div class="form-group">
                    <label class="form-label">Internal Admin Notes (Private)</label>
                    <textarea name="internal_notes" class="form-textarea" rows="3" placeholder="Notes visible only to Super Admin...">{{ $ticket->internal_notes }}</textarea>
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">Save Status & Notes</button>
            </form>
        </div>
    </div>
</div>
@endsection
