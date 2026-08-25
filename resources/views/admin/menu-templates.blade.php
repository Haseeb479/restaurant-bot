@extends('layouts.admin')
@section('title', 'Global Menu Templates')
@section('header_title', 'Master Menu Templates')
@section('header_subtitle', 'Create pre-built menus by cuisine and clone them to any restaurant with one click')

@section('content')
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 22px;">
    <!-- List of Templates -->
    <div>
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Available Menu Templates</h3>
                    <p>Pre-configured dish catalogs ready for cloning into tenant menus</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                @forelse($templates as $tpl)
                    <div style="background: var(--bg-page); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <div>
                                <h4 style="font-size: 14px; font-weight: 800;">{{ $tpl->name }}</h4>
                                <span class="badge badge-blue" style="margin-top: 3px;">{{ $tpl->cuisine_type }}</span>
                                @if($tpl->description)
                                    <p style="font-size: 11.5px; color: var(--text-secondary); margin-top: 4px;">{{ $tpl->description }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.menu-templates.delete', $tpl->id) }}" onsubmit="return confirm('Delete this menu template?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>

                        <!-- Template Items Preview -->
                        <div style="margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                            <div style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 6px;">
                                Items Included ({{ $tpl->items->count() }})
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                @foreach($tpl->items as $item)
                                    <span style="font-size: 11.5px; background: var(--card-bg); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: 6px;">
                                        <strong>{{ $item->item_name }}</strong> (Rs. {{ number_format($item->price) }})
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Clone Action -->
                        <div style="margin-top: 14px; display: flex; align-items: center; gap: 8px;">
                            <select id="cloneRestSelect_{{ $tpl->id }}" class="form-select" style="max-width: 260px; font-size: 12px;">
                                <option value="">Select Restaurant to Clone To...</option>
                                @foreach($restaurants as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->city ?: 'N/A' }})</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-success btn-sm" onclick="cloneTemplate('{{ $tpl->id }}')">📋 Clone Menu</button>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--text-secondary); padding: 25px;">
                        No master menu templates created yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Create New Master Template -->
    <div>
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Create Master Template</h3>
                    <p>Define a new cuisine package</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.menu-templates.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Template Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Chai & Snacks / Cafe" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Cuisine Category *</label>
                    <input type="text" name="cuisine_type" class="form-input" placeholder="e.g. Fast Food, Desi, Desserts" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="2" placeholder="Brief notes on dishes in this package"></textarea>
                </div>

                <h4 style="font-size: 12px; font-weight: 800; margin: 14px 0 8px;">Add Dishes (Up to 4 Initial Items)</h4>
                @for($i = 0; $i < 4; $i++)
                    <div style="background: var(--bg-page); padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 8px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 6px;">
                            <input type="text" name="items[{{ $i }}][category_name]" class="form-input" placeholder="Category (e.g. Burgers)">
                            <input type="number" name="items[{{ $i }}][price]" class="form-input" placeholder="Price (PKR)">
                        </div>
                        <input type="text" name="items[{{ $i }}][item_name]" class="form-input" placeholder="Item Name (e.g. Special Zinger)">
                    </div>
                @endfor

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Create Master Template</button>
            </form>
        </div>
    </div>
</div>

<form id="globalCloneForm" method="POST" action="" style="display: none;">
    @csrf
</form>

<script>
function cloneTemplate(tplId) {
    const sel = document.getElementById('cloneRestSelect_' + tplId);
    if (!sel.value) {
        alert('Please choose a target restaurant.');
        return;
    }
    if (confirm('Clone items from this template to the chosen restaurant?')) {
        const form = document.getElementById('globalCloneForm');
        form.action = '/admin/menu-templates/' + tplId + '/clone/' + sel.value;
        form.submit();
    }
}
</script>
@endsection
