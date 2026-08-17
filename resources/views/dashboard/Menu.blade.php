@extends('layouts.dashboard')
@section('content')

<!-- Page Header & Action Bar -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">Menu Management</h1>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Organize categories, food items, prices, and visual flyers for your AI bot</p>
    </div>

    <!-- Quick Actions -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button onclick="openModal('modal-add-item')" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px; font-weight: 600; padding: 10px 16px; border-radius: 10px;">
            <span>➕</span> Add Food Item
        </button>
        <button onclick="openModal('modal-add-category')" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-weight: 600; padding: 10px 14px; border-radius: 10px;">
            📁 New Category
        </button>
        <button onclick="openModal('modal-bulk-csv')" class="btn" style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-weight: 600; padding: 10px 14px; border-radius: 10px;">
            📊 Import CSV / Excel
        </button>
        <button onclick="openModal('modal-menu-flyer')" class="btn" style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; font-weight: 600; padding: 10px 14px; border-radius: 10px;">
            🖼️ Menu Flyer
        </button>
    </div>
</div>

<!-- Category Filter Tabs -->
<div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 24px; scrollbar-width: none;">
    @php
        $totalItemsCount = $categories->sum(fn($c) => $c->items->count());
    @endphp
    <button onclick="filterCategory('all')" id="tab-all" class="cat-pill active-pill">
        All Items <span class="pill-count">({{ $totalItemsCount }})</span>
    </button>
    @foreach($categories as $cat)
        <button onclick="filterCategory('cat-{{ $cat->id }}')" id="tab-cat-{{ $cat->id }}" class="cat-pill">
            {{ $cat->name }} <span class="pill-count">({{ $cat->items->count() }})</span>
        </button>
    @endforeach
</div>

<!-- Menu Categories & Items List -->
@if($categories->count() === 0)
    <!-- Empty State -->
    <div class="card" style="padding: 60px 20px; text-align: center; border: 2px dashed #cbd5e1; background: #ffffff;">
        <div style="font-size: 48px; margin-bottom: 12px;">🍔</div>
        <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">Your menu is empty</h3>
        <p style="font-size: 13px; color: #64748b; max-width: 420px; margin: 0 auto 20px;">
            Add food items manually or import your entire menu from an Excel / CSV sheet in just 1 click.
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button onclick="openModal('modal-bulk-csv')" class="btn btn-primary">📊 Upload CSV Sheet</button>
            <button onclick="openModal('modal-add-category')" class="btn" style="border: 1px solid #cbd5e1;">📁 Create Category</button>
        </div>
    </div>
@else
    <div id="categories-container" style="display: flex; flex-direction: column; gap: 32px;">
        @foreach($categories as $category)
            <div class="category-block" id="cat-{{ $category->id }}">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2 style="font-size: 17px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em;">
                            {{ $category->name }}
                        </h2>
                        <span style="font-size: 12px; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 99px; font-weight: 600;">
                            {{ $category->items->count() }} {{ Str::plural('item', $category->items->count()) }}
                        </span>
                    </div>

                    <button onclick="openAddItemWithCategory('{{ $category->id }}', '{{ $category->name }}')" style="background: none; border: none; font-size: 12px; font-weight: 600; color: #2563eb; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                        + Add item to {{ $category->name }}
                    </button>
                </div>

                @if($category->items->count() === 0)
                    <div style="background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center; color: #94a3b8; font-size: 13px;">
                        No items in this category yet.
                    </div>
                @else
                    <!-- Grid of Item Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                        @foreach($category->items as $item)
                            <div class="menu-card {{ $item->is_available ? '' : 'item-disabled' }}">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px;">
                                    <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.3;">
                                        {{ $item->name }}
                                    </h3>
                                    <!-- Toggle Availability Switch -->
                                    <form method="POST" action="/dashboard/{{ $restaurant->id }}/menu/item/{{ $item->id }}/toggle">
                                        @csrf
                                        <button type="submit" class="toggle-btn {{ $item->is_available ? 'toggle-on' : 'toggle-off' }}" title="Click to toggle item availability">
                                            {{ $item->is_available ? '● In Stock' : '○ Out' }}
                                        </button>
                                    </form>
                                </div>

                                @if($item->description)
                                    <p style="font-size: 12px; color: #64748b; line-height: 1.4; margin-bottom: 12px; min-height: 32px;">
                                        {{ $item->description }}
                                    </p>
                                @else
                                    <p style="font-size: 12px; color: #94a3b8; font-style: italic; margin-bottom: 12px; min-height: 32px;">
                                        No description
                                    </p>
                                @endif

                                <!-- Price / Sizes Section -->
                                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 8px 12px; margin-bottom: 12px;">
                                    @if($item->hasSizes())
                                        <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px;">Size Options:</div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            @foreach($item->sizes as $size)
                                                <span style="font-size: 12px; font-weight: 600; background: #ffffff; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 6px; color: #0f172a;">
                                                    <strong>{{ $size['size'] }}:</strong> Rs. {{ number_format($size['price'], 0) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 12px; color: #64748b; font-weight: 600;">Price:</span>
                                            <span style="font-size: 15px; font-weight: 800; color: #0f172a;">
                                                Rs. {{ number_format($item->price, 0) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Card Footer: Delete Action -->
                                <div style="display: flex; justify-content: flex-end; border-top: 1px solid #f1f5f9; pt-2; margin-top: 8px;">
                                    <form method="POST" action="/dashboard/{{ $restaurant->id }}/menu/item/{{ $item->id }}" onsubmit="return confirm('Delete {{ $item->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 12px; font-weight: 500; cursor: pointer; padding: 4px 8px; border-radius: 6px;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif

<!-- ========================================================================= -->
<!-- MODALS -->
<!-- ========================================================================= -->

<!-- 1. Modal: Add Food Item -->
<div id="modal-add-item" class="modal-backdrop" style="display: none;">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 17px; font-weight: 700; color: #0f172a;">➕ Add Food Item</h3>
            <button onclick="closeModal('modal-add-item')" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="/dashboard/{{ $restaurant->id }}/menu/item">
            @csrf
            <div style="margin-bottom: 14px;">
                <label class="form-label">Category</label>
                <select name="category_id" id="item-category-select" class="form-input" required>
                    <option value="">Select a category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 14px;">
                <label class="form-label">Item Name</label>
                <input type="text" name="name" class="form-input" placeholder="e.g. Crispy Zinger Burger" required>
            </div>

            <div style="margin-bottom: 14px;">
                <label class="form-label">Description (Optional)</label>
                <input type="text" name="description" class="form-input" placeholder="e.g. Crispy fillet with secret mayo sauce and iceberg">
            </div>

            <!-- Single Price -->
            <div id="modal-single-price-section" style="margin-bottom: 14px;">
                <label class="form-label">Price (Rs.)</label>
                <input type="number" name="price" id="modal-single-price" class="form-input" placeholder="e.g. 350" value="0">
            </div>

            <!-- Sizes Toggle -->
            <div style="margin-bottom: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="modal-has-sizes" onchange="toggleModalSizes()" style="width: 16px; height: 16px;">
                    This item has size variants (Small / Medium / Large)
                </label>

                <!-- Sizes Builder Container -->
                <div id="modal-sizes-builder" style="display: none; margin-top: 12px;">
                    <div id="sizes-rows-container" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;">
                        <div class="size-input-row">
                            <input type="text" name="sizes[0][size]" placeholder="Size (e.g. M)" class="form-input" style="width: 90px; text-transform: uppercase;">
                            <input type="number" name="sizes[0][price]" placeholder="Price (Rs.)" class="form-input" style="flex: 1;">
                            <button type="button" onclick="removeSizeRow(this)" style="background: none; border: none; color: #ef4444; font-size: 18px; cursor: pointer;">&times;</button>
                        </div>
                        <div class="size-input-row">
                            <input type="text" name="sizes[1][size]" placeholder="Size (e.g. L)" class="form-input" style="width: 90px; text-transform: uppercase;">
                            <input type="number" name="sizes[1][price]" placeholder="Price (Rs.)" class="form-input" style="flex: 1;">
                            <button type="button" onclick="removeSizeRow(this)" style="background: none; border: none; color: #ef4444; font-size: 18px; cursor: pointer;">&times;</button>
                        </div>
                    </div>
                    <button type="button" onclick="addSizeRow()" style="background: #eff6ff; border: 1px dashed #bfdbfe; color: #1d4ed8; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; width: 100%;">
                        + Add Another Size Variant
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('modal-add-item')" class="btn" style="border: 1px solid #cbd5e1;">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal: Add Category -->
<div id="modal-add-category" class="modal-backdrop" style="display: none;">
    <div class="modal-box" style="max-width: 400px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 17px; font-weight: 700; color: #0f172a;">📁 Create New Category</h3>
            <button onclick="closeModal('modal-add-category')" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="/dashboard/{{ $restaurant->id }}/menu/category">
            @csrf
            <div style="margin-bottom: 14px;">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-input" placeholder="e.g. Burgers, Biryani, Hot Beverages" required>
            </div>

            <div style="margin-bottom: 14px;">
                <label class="form-label">Sort Order (Optional)</label>
                <input type="number" name="sort_order" class="form-input" value="0" placeholder="0">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('modal-add-category')" class="btn" style="border: 1px solid #cbd5e1;">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal: Bulk CSV / Excel Upload -->
<div id="modal-bulk-csv" class="modal-backdrop" style="display: none;">
    <div class="modal-box" style="max-width: 480px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 17px; font-weight: 700; color: #0f172a;">📊 Bulk Import Menu (CSV / Excel)</h3>
            <button onclick="closeModal('modal-bulk-csv')" class="modal-close">&times;</button>
        </div>

        <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
            Upload your CSV file exported from Excel or Google Sheets. Categories, items, prices, and size variants will be imported automatically.
        </p>

        <div style="background: #f1f5f9; border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 12px; color: #475569;">
            <strong>Expected Columns:</strong><br>
            <code>Category, Item Name, Price, Sizes, Description</code>
            <div style="margin-top: 8px;">
                <a href="{{ route('dashboard.sample-menu-csv', $restaurant->id) }}" style="color: #2563eb; font-weight: 600; text-decoration: underline;">
                    📥 Download Ready-to-Use Sample Template
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.upload-menu-csv', $restaurant->id) }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 16px;">
                <label class="form-label">Select CSV File</label>
                <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" required class="form-input" style="padding: 8px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('modal-bulk-csv')" class="btn" style="border: 1px solid #cbd5e1;">Cancel</button>
                <button type="submit" class="btn btn-primary">Start Import</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal: Menu Poster / Flyer Image -->
<div id="modal-menu-flyer" class="modal-backdrop" style="display: none;">
    <div class="modal-box" style="max-width: 440px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 17px; font-weight: 700; color: #0f172a;">🖼️ Menu Poster / Flyer</h3>
            <button onclick="closeModal('modal-menu-flyer')" class="modal-close">&times;</button>
        </div>

        <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
            Upload your restaurant's official menu picture. When customers ask for the menu on WhatsApp, the bot sends this photo directly!
        </p>

        @if($restaurant->menu_image)
            <div style="margin-bottom: 16px; text-align: center; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <img src="/{{ ltrim($restaurant->menu_image, '/') }}" alt="Menu Flyer" style="max-height: 160px; max-width: 100%; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 8px;">
                <div>
                    <span style="font-size: 12px; color: #166534; font-weight: 600; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">✓ Active Menu Flyer</span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.upload-menu-image', $restaurant->id) }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 16px;">
                <label class="form-label">Upload New Image (JPG / PNG)</label>
                <input type="file" name="menu_image" accept="image/*" required class="form-input" style="padding: 8px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('modal-menu-flyer')" class="btn" style="border: 1px solid #cbd5e1;">Cancel</button>
                <button type="submit" class="btn btn-success">Save Menu Flyer</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- STYLES & INTERACTIVITY -->
<!-- ========================================================================= -->
<style>
/* Category Pills */
.cat-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 999px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.cat-pill:hover { border-color: #cbd5e1; color: #0f172a; }
.active-pill { background: #0f172a !important; color: #ffffff !important; border-color: #0f172a !important; }
.pill-count { opacity: 0.7; font-size: 11px; margin-left: 2px; }

/* Menu Card */
.menu-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.menu-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);
    transform: translateY(-2px);
}
.item-disabled { opacity: 0.6; background: #f8fafc; }

/* Toggle Button */
.toggle-btn {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 999px;
    border: none;
    cursor: pointer;
    transition: 0.15s;
}
.toggle-on  { background: #dcfce7; color: #166534; }
.toggle-off { background: #f1f5f9; color: #64748b; }

/* Modal Styles */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.modal-box {
    background: #ffffff;
    border-radius: 18px;
    width: 100%;
    max-width: 480px;
    padding: 24px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    max-height: 90vh;
    overflow-y: auto;
}
.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
}
.modal-close:hover { color: #0f172a; }

/* Form Controls */
.form-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 5px;
}
.form-input {
    width: 100%;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #0f172a;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 13px;
    transition: 0.15s;
}
.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.size-input-row { display: flex; gap: 8px; align-items: center; }
</style>

<script>
    // Modal Helpers
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Category Filter Pills
    function filterCategory(catId) {
        // Update pill active class
        document.querySelectorAll('.cat-pill').forEach(el => el.classList.remove('active-pill'));
        if (catId === 'all') {
            document.getElementById('tab-all').classList.add('active-pill');
            document.querySelectorAll('.category-block').forEach(el => el.style.display = 'block');
        } else {
            document.getElementById('tab-' + catId).classList.add('active-pill');
            document.querySelectorAll('.category-block').forEach(el => {
                el.style.display = (el.id === catId) ? 'block' : 'none';
            });
        }
    }

    function openAddItemWithCategory(catId, catName) {
        document.getElementById('item-category-select').value = catId;
        openModal('modal-add-item');
    }

    // Sizes Builder in Modal
    function toggleModalSizes() {
        const checkbox = document.getElementById('modal-has-sizes');
        const builder  = document.getElementById('modal-sizes-builder');
        const singleSec= document.getElementById('modal-single-price-section');
        if (checkbox.checked) {
            builder.style.display = 'block';
            singleSec.style.display = 'none';
        } else {
            builder.style.display = 'none';
            singleSec.style.display = 'block';
        }
    }

    let sizeRowIndex = 2;
    function addSizeRow() {
        const container = document.getElementById('sizes-rows-container');
        const row = document.createElement('div');
        row.className = 'size-input-row';
        row.innerHTML = `
            <input type="text" name="sizes[${sizeRowIndex}][size]" placeholder="Size (e.g. XL)" class="form-input" style="width: 90px; text-transform: uppercase;">
            <input type="number" name="sizes[${sizeRowIndex}][price]" placeholder="Price (Rs.)" class="form-input" style="flex: 1;">
            <button type="button" onclick="removeSizeRow(this)" style="background: none; border: none; color: #ef4444; font-size: 18px; cursor: pointer;">&times;</button>
        `;
        container.appendChild(row);
        sizeRowIndex++;
    }

    function removeSizeRow(btn) {
        btn.closest('.size-input-row').remove();
    }
</script>

@endsection