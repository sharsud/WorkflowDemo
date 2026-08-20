<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (session_user()) {
    $redirect = $_GET['redirect'] ?? 'index.php';
    header('Location: ' . asset_url($redirect));
    exit;
}

$redirect = $_GET['redirect'] ?? '';
render_header('Login', 'login');
?>
<div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <h1 class="text-2xl font-bold" data-testid="page-title">Sign in</h1>
    <p class="mt-1 text-sm text-slate-500">Session cookies plus Basic Auth are accepted on every API route.</p>
    <form id="form-login" class="mt-6 space-y-4" data-testid="form-login">
        <input type="hidden" id="input-redirect" name="redirect" value="<?= e($redirect) ?>" data-testid="input-redirect">
        <label class="block text-sm">Username
            <input type="text" name="username" id="input-username" autocomplete="username" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-username">
        </label>
        <label class="block text-sm">Password
            <input type="password" name="password" id="input-password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-password">
        </label>
        <p id="login-error" class="hidden rounded-lg bg-rose-50 p-3 text-sm text-rose-700" data-testid="alert-error"></p>
        <p id="login-success" class="hidden rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700" data-testid="alert-success"></p>
        <button type="submit" id="btn-login" class="w-full rounded-lg bg-indigo-600 py-2.5 font-semibold text-white hover:bg-indigo-700" data-testid="btn-login">Login</button>
    </form>
    <div class="mt-6 space-y-2" data-testid="demo-credentials">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fill demo credentials</p>
        <div class="flex flex-col gap-2">
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-left text-sm hover:bg-slate-50" data-testid="btn-fill-standard" data-username="standard_user" data-password="standard123">standard_user / standard123</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-left text-sm hover:bg-slate-50" data-testid="btn-fill-locked" data-username="locked_user" data-password="locked123">locked_user / locked123 (locked)</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-left text-sm hover:bg-slate-50" data-testid="btn-fill-admin" data-username="admin_user" data-password="admin123">admin_user / admin123</button>
        </div>
        <p class="pt-2 text-xs text-slate-500" data-testid="basic-auth-hint">Basic Auth example for standard_user: <code>c3RhbmRhcmRfdXNlcjpzdGFuZGFyZDEyMw==</code> (also accepts password123 / <code>c3RhbmRhcmRfdXNlcjpwYXNzd29yZDEyMw==</code>).</p>
    </div>
</div>
<?php render_footer(); ?>
