<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0efe9; min-height: 100vh; }
        nav { background: #0e0e10; height: 54px; padding: 0 1.75rem; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .wm-icon { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; width: 22px; height: 22px; }
        .wm-sq { border-radius: 2px; }
        .wm-sq:nth-child(1),.wm-sq:nth-child(4) { background: #fff; }
        .wm-sq:nth-child(2),.wm-sq:nth-child(3) { background: rgba(255,255,255,0.25); }
        .brand-text { font-size: 13px; font-weight: 500; color: #fff; letter-spacing: -0.01em; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-badge { font-size: 11px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.5); border-radius: 99px; padding: 3px 10px; border: 0.5px solid rgba(255,255,255,0.1); }
        .logout-btn { font-size: 12px; color: rgba(255,255,255,0.45); background: none; border: 0.5px solid rgba(255,255,255,0.12); border-radius: 7px; padding: 5px 14px; cursor: pointer; }
        .logout-btn:hover { color: #fff; border-color: rgba(255,255,255,0.3); }
        .body { max-width: 1100px; margin: 1.75rem auto; padding: 0 1.25rem; }
        .success-bar { background: #eaf4ee; border: 0.5px solid #c0dd97; border-radius: 8px; padding: 10px 16px; font-size: 12px; color: #27500A; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
        .s-bar-dot { width: 6px; height: 6px; border-radius: 50%; background: #3B6D11; flex-shrink: 0; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 1.75rem; }
        .stat { background: #0e0e10; border-radius: 12px; padding: 1.25rem 1.5rem; }
        .stat-num { font-size: 26px; font-weight: 500; color: #fff; letter-spacing: -0.03em; margin-bottom: 4px; }
        .stat-label { font-size: 11px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; }
        .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .section-title { font-size: 12px; font-weight: 500; color: #888; text-transform: uppercase; letter-spacing: 0.07em; }
        .add-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; background: #0e0e10; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; cursor: pointer; text-decoration: none; }
        .add-btn:hover { background: #2a2a2e; }
        .table { background: #fff; border-radius: 12px; border: 0.5px solid #e8e8e4; overflow: hidden; }
        .t-head { display: grid; grid-template-columns: 2fr 1fr 1fr 1.2fr; padding: 10px 1.25rem; border-bottom: 0.5px solid #f0efe9; }
        .t-head span { font-size: 11px; font-weight: 500; color: #bbb; text-transform: uppercase; letter-spacing: 0.05em; }
        .t-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1.2fr; padding: 14px 1.25rem; border-bottom: 0.5px solid #f5f5f2; align-items: center; }
        .t-row:last-child { border-bottom: none; }
        .r-name { font-size: 14px; font-weight: 500; color: #111; margin-bottom: 3px; }
        .r-meta { font-size: 12px; color: #bbb; }
        .plan-pill { display: inline-flex; font-size: 11px; font-weight: 500; border-radius: 99px; padding: 3px 10px; }
        .plan-trial { background: #faeeda; color: #633806; }
        .plan-pro { background: #EEEDFE; color: #3C3489; }
        .status-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; border-radius: 99px; padding: 3px 10px; }
        .s-active { background: #eaf4ee; color: #27500A; }
        .s-inactive { background: #f5f5f2; color: #aaa; }
        .s-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .action-row { display: flex; align-items: center; gap: 8px; }
        .view-btn { font-size: 11px; color: #555; background: #f5f5f2; border: 0.5px solid #e8e8e4; border-radius: 6px; padding: 4px 10px; cursor: pointer; text-decoration: none; }
        .view-btn:hover { background: #ececea; }
        .switch { position: relative; display: inline-block; width: 36px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #d1d5db; border-radius: 999px; transition: 0.2s; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.2s; }
        input:checked + .slider { background: #0e0e10; }
        input:checked + .slider:before { transform: translateX(16px); }
        @media(max-width: 640px) { .t-head,.t-row { grid-template-columns: 1fr; } .t-head { display: none; } }
    </style>
</head>
<body>

<nav>
    <div class="nav-left">
        <div class="wm-icon">
            <div class="wm-sq"></div><div class="wm-sq"></div>
            <div class="wm-sq"></div><div class="wm-sq"></div>
        </div>
        <span class="brand-text">Restaurant admin</span>
    </div>
    <div class="nav-right">
        <span class="nav-badge">Super admin</span>
        <form method="POST" action="<?php echo e(route('admin.logout')); ?>" style="display:inline;">
            <?php echo csrf_field(); ?>
            <button type="submit" class="logout-btn">Sign out</button>
        </form>
    </div>
</nav>

<div class="body">
    <?php if(session('success')): ?>
        <div class="success-bar"><div class="s-bar-dot"></div><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat">
            <div class="stat-num"><?php echo e($totalOrders); ?></div>
            <div class="stat-label">Orders today</div>
        </div>
        <div class="stat">
            <div class="stat-num">Rs <?php echo e(number_format($totalRevenue)); ?></div>
            <div class="stat-label">Revenue today</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?php echo e($restaurants->count()); ?></div>
            <div class="stat-label">Restaurants</div>
        </div>
    </div>

    <div class="section-head">
        <span class="section-title">Restaurants</span>
        <a href="<?php echo e(route('admin.create-restaurant')); ?>" class="add-btn">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 1v10M1 6h10" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
            Add restaurant
        </a>
    </div>

    <div class="table">
        <div class="t-head">
            <span>Name</span>
            <span>Plan</span>
            <span>Status</span>
            <span>Actions</span>
        </div>

        <?php $__empty_1 = true; $__currentLoopData = $restaurants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="t-row">
            <div>
                <div class="r-name"><?php echo e($r->name); ?></div>
                <div class="r-meta"><?php echo e($r->orders_count); ?> orders · <?php echo e($r->menu_items_count); ?> items
                    <?php if($r->plan_expires_at): ?> · Expires <?php echo e($r->plan_expires_at->format('M j, Y')); ?> <?php endif; ?>
                </div>
            </div>
            <div>
                <span class="plan-pill <?php echo e($r->plan === 'pro' ? 'plan-pro' : 'plan-trial'); ?>">
                    <?php echo e(ucfirst($r->plan)); ?>

                </span>
            </div>
            <div>
                <span class="status-pill <?php echo e($r->is_active ? 's-active' : 's-inactive'); ?>">
                    <span class="s-dot"></span><?php echo e($r->is_active ? 'Active' : 'Inactive'); ?>

                </span>
            </div>
            <div class="action-row">
                <a href="/dashboard/<?php echo e($r->id); ?>/login" class="view-btn" target="_blank">View</a>
                <form method="POST" action="<?php echo e(route('admin.toggle-restaurant', $r->id)); ?>" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <label class="switch">
                        <input type="checkbox" onchange="this.form.submit()" <?php echo e($r->is_active ? 'checked' : ''); ?>>
                        <span class="slider"></span>
                    </label>
                </form>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="padding:2rem 1.25rem; text-align:center; color:#bbb; font-size:13px;">
            No restaurants yet. Add your first one above.
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html><?php /**PATH C:\Users\Seeb\restaurant-bot\restaurant-bot\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>