
<?php $__env->startSection('content'); ?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin: 1.5rem 0;
}
.stat-card {
    background: #fff;
    border: 1px solid #e8e8e4;
    border-radius: 14px;
    padding: 16px;
}
.stat-label { font-size: 12px; color: #888; }
.stat-value { font-size: 22px; font-weight: 600; margin: 6px 0; }
.stat-sub   { font-size: 11px; color: #aaa; }

.alert {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 1.5rem;
    font-size: 14px;
}

.card {
    background: #fff;
    border: 1px solid #e8e8e4;
    border-radius: 16px;
    overflow: hidden;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 18px;
    border-bottom: 1px solid #eee;
    flex-wrap: wrap;
    gap: 12px;
}
.card-header h2 { font-size: 16px; font-weight: 600; }

.btn {
    background: #0e0e10;
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    cursor: pointer;
    border: none;
}
.btn:hover { background: #2a2a2e; }

.table { width: 100%; border-collapse: collapse; }
.table th {
    text-align: left;
    font-size: 11px;
    color: #888;
    padding: 12px;
    background: #fafafa;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.table td {
    padding: 12px;
    border-top: 1px solid #f0f0f0;
    vertical-align: top;
    font-size: 13px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
}
.status-pending          { background:#f3f4f6; color:#374151; }
.status-confirmed        { background:#ecfeff; color:#155e75; }
.status-preparing        { background:#fff7ed; color:#9a3412; }
.status-out_for_delivery { background:#f0fdf4; color:#166534; }
.status-delivered        { background:#dcfce7; color:#166534; }
.status-cancelled        { background:#fee2e2; color:#991b1b; }

.tracking-code {
    font-family: monospace;
    font-size: 12px;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 6px;
    color: #374151;
    font-weight: 600;
}

@media(max-width: 900px){
    .stats-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="page-header">
    <h1>Live Orders</h1>
    <p><?php echo e(now()->format('l, d F Y')); ?> — Auto-refreshes every 30 seconds</p>
</div>


<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Today's Orders</div>
        <div class="stat-value"><?php echo e($today->count()); ?></div>
        <div class="stat-sub">since midnight</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Revenue</div>
        <div class="stat-value">Rs. <?php echo e(number_format($today->sum('total'), 0)); ?></div>
        <div class="stat-sub">today</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active</div>
        <div class="stat-value">
            <?php echo e($today->whereIn('status', ['pending','confirmed','preparing','out_for_delivery'])->count()); ?>

        </div>
        <div class="stat-sub">needs action</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Delivered</div>
        <div class="stat-value"><?php echo e($today->where('status','delivered')->count()); ?></div>
        <div class="stat-sub">completed</div>
    </div>
</div>


<?php $pendingOrders = $today->where('status','pending') ?>
<?php if($pendingOrders->count() > 0): ?>
<div class="alert">
    🔔 <strong><?php echo e($pendingOrders->count()); ?> new order(s) waiting for confirmation!</strong>
</div>
<?php endif; ?>


<div class="card">
    <div class="card-header">
        <h2>All Orders</h2>
        <button class="btn" onclick="location.reload()">↻ Refresh</button>
    </div>

    <?php if($orders->count() === 0): ?>
        <div style="text-align:center; padding:40px; color:#888; font-size:14px;">
            No orders yet. Orders placed via WhatsApp will appear here.
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tracking</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><strong>#<?php echo e($order->id); ?></strong></td>

                    <td>
                        <span class="tracking-code"><?php echo e($order->tracking_code); ?></span>
                    </td>

                    <td>
                        <?php if($order->customer_name): ?>
                            <strong><?php echo e($order->customer_name); ?></strong><br>
                        <?php endif; ?>
                        <span style="font-size:12px;color:#888;"><?php echo e($order->customer_phone); ?></span><br>
                        <span style="font-size:12px;">📍 <?php echo e(Str::limit($order->delivery_address, 35)); ?></span>
                    </td>

                    <td>
                        <?php $__empty_1 = true; $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div>
                                <?php echo e($item->name); ?>

                                <?php if($item->size): ?> <span style="font-size:11px;color:#888;">(<?php echo e($item->size); ?>)</span> <?php endif; ?>
                                × <?php echo e($item->quantity); ?>

                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <span style="color:#aaa;font-size:12px;"><?php echo e(Str::limit($order->notes, 40)); ?></span>
                        <?php endif; ?>
                    </td>

                    <td><strong>Rs. <?php echo e(number_format($order->total, 0)); ?></strong></td>

                    <td style="text-transform:uppercase;font-size:12px;">
                        <?php echo e(str_replace('_', ' ', $order->payment_method)); ?>

                    </td>

                    <td>
                        <span class="status-badge status-<?php echo e($order->status); ?>">
                            <?php echo e(ucwords(str_replace('_', ' ', $order->status))); ?>

                        </span>
                    </td>

                    <td style="font-size:12px;color:#888;">
                        <?php echo e($order->created_at->diffForHumans()); ?>

                    </td>

                    <td>
                        <?php if(!in_array($order->status, ['delivered','cancelled'])): ?>
                        <form method="POST"
                              action="<?php echo e(route('dashboard.update-status', [$restaurant->id, $order->id])); ?>">
                            <?php echo csrf_field(); ?>
                            <select name="status" onchange="this.form.submit()"
                                    style="padding:6px 8px;border-radius:6px;border:1px solid #e8e8e4;font-size:12px;cursor:pointer;">
                                <option value="pending"          <?php echo e($order->status==='pending'          ? 'selected':''); ?>>Pending</option>
                                <option value="confirmed"        <?php echo e($order->status==='confirmed'        ? 'selected':''); ?>>Confirmed</option>
                                <option value="preparing"        <?php echo e($order->status==='preparing'        ? 'selected':''); ?>>Preparing</option>
                                <option value="out_for_delivery" <?php echo e($order->status==='out_for_delivery' ? 'selected':''); ?>>Out for Delivery</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancel</option>
                            </select>
                        </form>
                        <?php else: ?>
                            <span style="color:#aaa;font-size:12px;">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <div style="padding:1rem;">
        <?php echo e($orders->links()); ?>

    </div>
    <?php endif; ?>
</div>

<script>
    // Auto-refresh every 30 seconds
    setTimeout(() => location.reload(), 30000);
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Seeb\restaurant-bot\restaurant-bot\resources\views/dashboard/orders.blade.php ENDPATH**/ ?>