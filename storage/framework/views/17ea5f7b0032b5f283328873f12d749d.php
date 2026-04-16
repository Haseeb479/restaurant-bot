<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0efe9; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem; }
        .page { display: grid; grid-template-columns: 1fr 1fr; border-radius: 18px; overflow: hidden; border: 1px solid #e0e0db; max-width: 780px; width: 100%; }
        .left { background: #0e0e10; padding: 3rem 2.5rem; display: flex; flex-direction: column; justify-content: space-between; min-height: 520px; }
        .wordmark { display: flex; align-items: center; gap: 8px; }
        .wm-icon { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; width: 26px; height: 26px; }
        .wm-sq { border-radius: 3px; }
        .wm-sq:nth-child(1), .wm-sq:nth-child(4) { background: #fff; }
        .wm-sq:nth-child(2), .wm-sq:nth-child(3) { background: rgba(255,255,255,0.25); }
        .wm-text { font-size: 13px; font-weight: 500; color: #fff; letter-spacing: -0.01em; }
        .left-quote { font-size: 22px; font-weight: 500; color: #fff; line-height: 1.4; letter-spacing: -0.02em; margin-bottom: 0.75rem; }
        .left-sub { font-size: 13px; color: rgba(255,255,255,0.4); line-height: 1.6; }
        .stat-row { display: flex; gap: 1.5rem; }
        .stat-num { font-size: 20px; font-weight: 500; color: #fff; }
        .stat-label { font-size: 11px; color: rgba(255,255,255,0.35); margin-top: 2px; }
        .right { background: #fff; padding: 3rem 2.5rem; display: flex; flex-direction: column; justify-content: center; }
        .right h2 { font-size: 20px; font-weight: 600; color: #111; margin-bottom: 5px; letter-spacing: -0.02em; }
        .hint { font-size: 13px; color: #888; margin-bottom: 2rem; }
        .error { background: #fff0f0; border: 1px solid #f5c1c1; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #a32d2d; margin-bottom: 1rem; }
        label { font-size: 11px; font-weight: 500; color: #999; text-transform: uppercase; letter-spacing: 0.06em; display: block; margin-bottom: 6px; }
        .input-wrap { position: relative; margin-bottom: 1rem; }
        input[type=password], input[type=text] { width: 100%; padding: 11px 40px 11px 14px; font-size: 14px; background: #f8f8f6; border: 1px solid #e8e8e4; border-radius: 10px; color: #111; outline: none; }
        input:focus { border-color: #aaa; box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }
        .eye-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #bbb; padding: 2px; }
        .btn { width: 100%; padding: 12px; font-size: 14px; font-weight: 500; background: #0e0e10; color: #fff; border: none; border-radius: 10px; cursor: pointer; margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:hover { background: #2a2a2e; }
        .arrow-ring { width: 20px; height: 20px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,0.35); display: flex; align-items: center; justify-content: center; }
        .secure { display: flex; align-items: center; gap: 6px; margin-top: 1.25rem; }
        .secure-dot { width: 6px; height: 6px; border-radius: 50%; background: #2d7a4f; }
        .secure span { font-size: 11px; color: #bbb; }
        @media(max-width: 580px) { .page { grid-template-columns: 1fr; } .left { display: none; } }
    </style>
</head>
<body>
<div class="page">
    <div class="left">
        <div class="wordmark">
            <div class="wm-icon">
                <div class="wm-sq"></div><div class="wm-sq"></div>
                <div class="wm-sq"></div><div class="wm-sq"></div>
            </div>
            <span class="wm-text">Restaurant admin</span>
        </div>
        <div>
            <p class="left-quote">Manage your restaurant from one place.</p>
            <p class="left-sub">Orders, menu, analytics and WhatsApp bot — all in one dashboard.</p>
        </div>
        <div class="stat-row">
            <div><div class="stat-num">15+</div><div class="stat-label">Menu items</div></div>
            <div><div class="stat-num">4</div><div class="stat-label">Categories</div></div>
            <div><div class="stat-num">24/7</div><div class="stat-label">Bot active</div></div>
        </div>
    </div>

    <div class="right">
        <h2>Welcome back</h2>
        <p class="hint">Sign in to your admin panel</p>

        <?php if($errors->any()): ?>
            <div class="error"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('admin.login')); ?>">
            <?php echo csrf_field(); ?>
            <label for="pw">Password</label>
            <div class="input-wrap">
                <input type="password" id="pw" name="password" placeholder="Enter your password" autofocus required />
                <button type="button" class="eye-btn" onclick="togglePw()">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="currentColor" stroke-width="1.2"/>
                        <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.2"/>
                    </svg>
                </button>
            </div>
            <button type="submit" class="btn">
                Sign in
                <div class="arrow-ring">
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                        <path d="M2 5h6M5.5 2.5L8 5l-2.5 2.5" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </button>
        </form>

        <div class="secure">
            <div class="secure-dot"></div>
            <span>256-bit encrypted · Secure session</span>
        </div>
    </div>
</div>
<script>
function togglePw() {
    const inp = document.getElementById('pw');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html><?php /**PATH C:\Users\Seeb\restaurant-bot\restaurant-bot\resources\views/admin/login.blade.php ENDPATH**/ ?>