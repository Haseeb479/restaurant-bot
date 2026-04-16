
<?php $__env->startSection('content'); ?>

<style>
.menu-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.card-dark {
    background: #0f1115;
    border: 1px solid rgba(255,255,255,0.04);
    border-radius: 14px;
    padding: 20px;
}

.card-dark h2 {
    font-size: 15px;
    margin-bottom: 16px;
    color: #e5e7eb;
}

.input {
    width: 100%;
    background: #0b0c10;
    border: 1px solid rgba(255,255,255,0.06);
    color: #e5e5e5;
    padding: 10px 11px;
    border-radius: 8px;
    font-size: 13px;
    transition: .2s;
    margin-bottom: 10px;
}

.input:focus { border-color: #2563eb; outline: none; }

.input-label {
    font-size: 11px;
    color: rgba(255,255,255,0.35);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 5px;
    display: block;
}

.btn { padding: 9px 14px; border-radius: 8px; border: none; font-size: 12px; cursor: pointer; transition: .2s; }
.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { opacity: .9; }
.btn-success { background: #16a34a; color: white; }
.btn-success:hover { opacity: .9; }
.btn-danger  { background: transparent; color: #f87171; }
.btn-danger:hover { background: rgba(248,113,113,.1); }
.btn-ghost   { background: transparent; color: #9ca3af; }
.btn-ghost:hover { color: white; }

/* sizes builder */
.sizes-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 12px;
    color: #9ca3af;
    cursor: pointer;
}

.sizes-builder { display: none; }
.sizes-builder.show { display: block; }

.size-row {
    display: grid;
    grid-template-columns: 80px 1fr 32px;
    gap: 8px;
    margin-bottom: 8px;
    align-items: center;
}

.size-row .input { margin-bottom: 0; }

.add-size-btn {
    background: rgba(37,99,235,0.1);
    color: #60a5fa;
    border: 1px dashed rgba(37,99,235,0.3);
    border-radius: 8px;
    padding: 7px;
    font-size: 12px;
    cursor: pointer;
    width: 100%;
    margin-bottom: 10px;
}

.remove-size {
    background: transparent;
    border: none;
    color: #f87171;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    padding: 4px;
}

/* menu categories */
.menu-category { margin-bottom: 28px; }

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.category-header h2 { font-size: 16px; font-weight: 600; }

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 14px;
}

.item-card {
    background: #0b0c10;
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 14px;
    transition: all .2s ease;
}

.item-card:hover {
    border-color: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.item-name { font-size: 14px; font-weight: 500; color: #f3f4f6; }
.item-desc { font-size: 12px; color: #6b7280; margin-top: 4px; }

.item-bottom {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.price { font-size: 14px; font-weight: 600; color: #f3f4f6; }
.price-sizes { font-size: 11px; color: #9ca3af; margin-top: 2px; }

.badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 999px;
    margin-top: 3px;
    display: inline-block;
}

.available   { background: rgba(34,197,94,.12); color: #4ade80; }
.unavailable { background: rgba(239,68,68,.12);  color: #f87171; }

.actions { display: flex; gap: 6px; }

@media(max-width: 768px) {
    .menu-grid { grid-template-columns: 1fr; }
}
</style>

<div class="page-header">
    <h1>Menu</h1>
    <p>Manage categories and items. Changes reflect on the bot instantly.</p>
</div>

<div class="menu-grid">

    
    <div class="card-dark">
        <h2>Add Category</h2>
        <form method="POST" action="/dashboard/<?php echo e($restaurant->id); ?>/menu/category">
            <?php echo csrf_field(); ?>
            <label class="input-label">Category Name</label>
            <input type="text" name="name" class="input" placeholder="e.g. Fresh Juices" required>

            <label class="input-label">Sort Order</label>
            <input type="number" name="sort_order" class="input" value="0">

            <button class="btn btn-primary">Create Category</button>
        </form>
    </div>

    
    <div class="card-dark">
        <h2>Add Item</h2>
        <form method="POST" action="/dashboard/<?php echo e($restaurant->id); ?>/menu/item" id="addItemForm">
            <?php echo csrf_field(); ?>

            <label class="input-label">Category</label>
            <select name="category_id" class="input" required>
                <option value="">Select category</option>
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <label class="input-label">Item Name</label>
            <input type="text" name="name" class="input" placeholder="e.g. Mango Juice" required>

            <label class="input-label">Description (optional)</label>
            <input type="text" name="description" class="input" placeholder="Short description">

            
            <div id="singlePriceSection">
                <label class="input-label">Price (Rs.)</label>
                <input type="number" name="price" id="singlePrice" class="input" placeholder="e.g. 150" value="0">
            </div>

            
            <label class="sizes-toggle" onclick="toggleSizes()">
                <input type="checkbox" id="hasSizesCheckbox" style="margin:0;">
                This item has size variants (M / L / etc.)
            </label>

            
            <div class="sizes-builder" id="sizesBuilder">
                <label class="input-label">Size Variants</label>
                <div id="sizeRows">
                    <div class="size-row">
                        <input type="text"   name="sizes[0][size]"  class="input" placeholder="M">
                        <input type="number" name="sizes[0][price]" class="input" placeholder="Price">
                        <button type="button" class="remove-size" onclick="removeSize(this)">×</button>
                    </div>
                    <div class="size-row">
                        <input type="text"   name="sizes[1][size]"  class="input" placeholder="L">
                        <input type="number" name="sizes[1][price]" class="input" placeholder="Price">
                        <button type="button" class="remove-size" onclick="removeSize(this)">×</button>
                    </div>
                </div>
                <button type="button" class="add-size-btn" onclick="addSizeRow()">+ Add size</button>
            </div>

            <button class="btn btn-success" style="width:100%;">Add Item</button>
        </form>
    </div>

</div>


<?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<div class="menu-category">

    <div class="category-header">
        <h2 style="color:#f3f4f6;"><?php echo e($cat->name); ?></h2>
        <span style="font-size:12px;color:#6b7280;"><?php echo e($cat->items->count()); ?> items</span>
    </div>

    <?php if($cat->items->isEmpty()): ?>
        <p style="color:#6b7280;font-size:13px;padding:10px 0;">No items yet. Add one above.</p>
    <?php else: ?>
    <div class="items-grid">
        <?php $__currentLoopData = $cat->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="item-card">

            <div class="item-name"><?php echo e($item->name); ?></div>

            <?php if($item->description): ?>
                <div class="item-desc"><?php echo e($item->description); ?></div>
            <?php endif; ?>

            <div class="item-bottom">
                <div>
                    <?php if($item->hasSizes()): ?>
                        <div class="price-sizes">
                            <?php $__currentLoopData = $item->sizes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span><?php echo e($s['size']); ?>: Rs.<?php echo e($s['price']); ?></span>
                                <?php if(!$loop->last): ?> &nbsp;/&nbsp; <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="price">Rs. <?php echo e(number_format($item->price, 0)); ?></div>
                    <?php endif; ?>

                    <span class="badge <?php echo e($item->is_available ? 'available' : 'unavailable'); ?>">
                        <?php echo e($item->is_available ? 'Available' : 'Hidden'); ?>

                    </span>
                </div>

                <div class="actions">
                    <form method="POST"
                          action="<?php echo e(route('dashboard.toggle-item', [$restaurant->id, $item->id])); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-ghost">
                            <?php echo e($item->is_available ? 'Hide' : 'Show'); ?>

                        </button>
                    </form>

                    <form method="POST"
                          action="<?php echo e(route('dashboard.delete-item', [$restaurant->id, $item->id])); ?>"
                          onsubmit="return confirm('Delete <?php echo e($item->name); ?>?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div style="text-align:center;padding:40px;color:#6b7280;font-size:14px;">
        No categories yet. Create one above to get started.
    </div>
<?php endif; ?>

<script>
let sizeCount = 2;

function toggleSizes() {
    const checkbox    = document.getElementById('hasSizesCheckbox');
    const builder     = document.getElementById('sizesBuilder');
    const singlePrice = document.getElementById('singlePriceSection');

    if (checkbox.checked) {
        builder.classList.add('show');
        singlePrice.style.display = 'none';
        document.getElementById('singlePrice').removeAttribute('required');
    } else {
        builder.classList.remove('show');
        singlePrice.style.display = 'block';
    }
}

function addSizeRow() {
    const container = document.getElementById('sizeRows');
    const row = document.createElement('div');
    row.className = 'size-row';
    row.innerHTML = `
        <input type="text"   name="sizes[${sizeCount}][size]"  class="input" placeholder="Size">
        <input type="number" name="sizes[${sizeCount}][price]" class="input" placeholder="Price">
        <button type="button" class="remove-size" onclick="removeSize(this)">×</button>
    `;
    container.appendChild(row);
    sizeCount++;
}

function removeSize(btn) {
    const rows = document.querySelectorAll('#sizeRows .size-row');
    if (rows.length > 1) {
        btn.closest('.size-row').remove();
    }
}
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Seeb\restaurant-bot\restaurant-bot\resources\views/dashboard/menu.blade.php ENDPATH**/ ?>