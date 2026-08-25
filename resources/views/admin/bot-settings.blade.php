@extends('layouts.admin')
@section('title', 'AI & Bot Configuration')
@section('header_title', 'AI Model & Bot Configuration')
@section('header_subtitle', 'Tune LLM parameters, temperature, token limits, and global system prompt')

@section('content')
<div style="max-width: 850px;">
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Global Bot Engine Parameters</h3>
                <p>These settings govern the default behavior of all connected WhatsApp bots</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.bot-settings.update') }}">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Default AI Model Engine *</label>
                    @php $defaultModel = \App\Models\Setting::get('ai_model_default', 'gemini-1.5-flash'); @endphp
                    <select name="ai_model_default" class="form-select" required>
                        <option value="gemini-1.5-flash" {{ $defaultModel === 'gemini-1.5-flash' ? 'selected' : '' }}>Google Gemini 1.5 Flash (Recommended - Fastest & Cost-Effective)</option>
                        <option value="gemini-1.5-pro" {{ $defaultModel === 'gemini-1.5-pro' ? 'selected' : '' }}>Google Gemini 1.5 Pro (Deep Multi-Turn Context)</option>
                        <option value="gpt-4o-mini" {{ $defaultModel === 'gpt-4o-mini' ? 'selected' : '' }}>OpenAI GPT-4o Mini</option>
                        <option value="gpt-4o" {{ $defaultModel === 'gpt-4o' ? 'selected' : '' }}>OpenAI GPT-4o (Full Flagship)</option>
                        <option value="claude-3-5-sonnet" {{ $defaultModel === 'claude-3-5-sonnet' ? 'selected' : '' }}>Anthropic Claude 3.5 Sonnet</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Global Message Rate Limit / Month *</label>
                    <input type="number" name="rate_limit_quota" class="form-input" value="{{ \App\Models\Setting::get('rate_limit_quota', 1000) }}" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Temperature (Creativity & Precision) *</label>
                    <input type="number" step="0.05" min="0" max="1" name="ai_temperature" class="form-input" value="{{ \App\Models\Setting::get('ai_temperature', 0.7) }}" required>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">Lower (0.2) = Strict order matching. Higher (0.8) = Friendly conversational sales assistant.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Max Response Tokens *</label>
                    <input type="number" name="ai_max_tokens" class="form-input" value="{{ \App\Models\Setting::get('ai_max_tokens', 800) }}" required>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">Recommended 500-1000 for concise WhatsApp chat bubbles.</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Global AI System Prompt Blueprint</label>
                <textarea name="system_prompt" class="form-textarea" rows="6" placeholder="You are an intelligent, polite AI ordering assistant for a Pakistani restaurant. You speak English and Roman Urdu...">{{ \App\Models\Setting::get('ai_system_prompt', '') }}</textarea>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">This system prompt serves as the master baseline and injects menu catalog, pricing, and active deals dynamically.</div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">Save Bot Settings</button>
        </form>
    </div>
</div>
@endsection
