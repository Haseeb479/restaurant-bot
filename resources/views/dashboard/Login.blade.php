<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — {{ $r->name }}</title>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f0efe9;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 1rem;
}

.page {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid #e0e0db;
    max-width: 780px;
    width: 100%;
}

/* LEFT PANEL */
.left {
    background: #0e0e10;
    padding: 3rem 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.left h1 {
    font-size: 22px;
    color: #fff;
    margin-bottom: 10px;
}

.left p {
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}

/* RIGHT PANEL */
.right {
    background: #fff;
    padding: 3rem 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right h2 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 6px;
}

.hint {
    font-size: 13px;
    color: #888;
    margin-bottom: 2rem;
}

/* FORM */
.error {
    background: #fff0f0;
    border: 1px solid #f5c1c1;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12px;
    color: #a32d2d;
    margin-bottom: 1rem;
}

label {
    font-size: 11px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 6px;
    display: block;
}

.input-wrap {
    position: relative;
    margin-bottom: 1rem;
}

input {
    width: 100%;
    padding: 11px 40px 11px 14px;
    font-size: 14px;
    background: #f8f8f6;
    border: 1px solid #e8e8e4;
    border-radius: 10px;
}

input:focus {
    border-color: #aaa;
    box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
    outline: none;
}

.btn {
    width: 100%;
    padding: 12px;
    background: #0e0e10;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    cursor: pointer;
    margin-top: 1.5rem;
}

.btn:hover {
    background: #2a2a2e;
}

@media(max-width: 580px) {
    .page { grid-template-columns: 1fr; }
    .left { display: none; }
}
</style>
</head>

<body>

<div class="page">

    <!-- LEFT SIDE -->
    <div class="left">
        <div>
            <h1>🍽️ {{ $r->name }}</h1>
            <p>Restaurant Dashboard</p>
        </div>

        <p>Manage orders, menu and customers from one place.</p>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right">

        <h2>Welcome back</h2>
        <p class="hint">Login to continue</p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/dashboard/{{ $r->id }}/login">
            @csrf

            <label>Password</label>
            <div class="input-wrap">
                <input type="password" name="password" placeholder="Enter your password" required autofocus>
            </div>

            <button type="submit" class="btn">Sign in →</button>
        </form>

    </div>

</div>

</body>
</html>