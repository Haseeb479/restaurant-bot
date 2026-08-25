@extends('layouts.admin')
@section('title', 'Feedback & Star Ratings')
@section('header_title', 'Customer & Restaurant Feedback')
@section('header_subtitle', 'Review customer feedback ratings, satisfaction, and comments')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Feedback Log</h3>
            <p>Customer and restaurant owner submissions</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rating</th>
                    <th>Restaurant</th>
                    <th>User Contact</th>
                    <th>Category</th>
                    <th>Comment</th>
                    <th>Submitted</th>
                    <th style="text-align: right;">Review Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feedbacks as $fb)
                    <tr>
                        <td>
                            <div style="color: #f59e0b; font-size: 14px;">
                                @for($i = 1; $i <= 5; $i++)
                                    {{ $i <= $fb->rating ? '★' : '☆' }}
                                @endfor
                            </div>
                        </td>
                        <td><strong>{{ $fb->restaurant->name ?? 'Platform / General' }}</strong></td>
                        <td>
                            <div>{{ $fb->user_name ?: 'Anonymous' }}</div>
                            <div style="font-size: 11px; color: var(--text-secondary);">{{ $fb->user_phone }}</div>
                        </td>
                        <td><span class="badge badge-gray">{{ ucfirst($fb->category) }}</span></td>
                        <td>{{ $fb->comment ?: 'No written comment' }}</td>
                        <td style="font-size: 11.5px; color: var(--text-secondary);">{{ $fb->created_at->diffForHumans() }}</td>
                        <td style="text-align: right;">
                            @if($fb->is_reviewed)
                                <span class="badge badge-green">Reviewed ✓</span>
                            @else
                                <form method="POST" action="{{ route('admin.feedback.reviewed', $fb->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">Mark Reviewed</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 25px;">No feedback entries recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 14px;">
        {{ $feedbacks->links() }}
    </div>
</div>
@endsection
