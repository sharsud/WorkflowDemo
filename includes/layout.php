<?php
declare(strict_types=1);

function render_header(string $title, string $page): void
{
    $user = session_user();
    $cart = enrich_cart(read_cart());
    $base = app_base_path();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="<?= e($base) ?>">
    <title><?= e($title) ?> | QADemo Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eef2ff', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800" data-page="<?= e($page) ?>" data-testid="page-<?= e($page) ?>">
<div id="loading-spinner" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40" data-testid="loading-indicator" aria-live="polite" aria-busy="true">
    <div class="flex flex-col items-center gap-3 rounded-xl bg-white px-8 py-6 shadow-xl">
        <div class="h-10 w-10 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600" data-testid="spinner-icon"></div>
        <p class="text-sm font-medium text-slate-600" data-testid="loading-text">Loading…</p>
    </div>
</div>
<div id="toast" class="hidden fixed right-4 top-4 z-50 max-w-sm rounded-lg px-4 py-3 text-sm shadow-lg" data-testid="toast" role="status"></div>
<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur" data-testid="site-header">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
        <a href="<?= e(asset_url('index.php')) ?>" class="flex items-center gap-2 font-semibold text-indigo-700" data-testid="link-home">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">Q</span>
            <span>QADemo Shop</span>
        </a>
        <nav class="flex flex-wrap items-center gap-3 text-sm font-medium" data-testid="main-nav">
            <a href="<?= e(asset_url('index.php')) ?>" class="hover:text-indigo-700" data-testid="link-catalog">Catalog</a>
            <a href="<?= e(asset_url('cart.php')) ?>" class="hover:text-indigo-700" data-testid="link-cart">
                Cart <span id="cart-count" class="ml-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs text-white" data-testid="cart-count"><?= (int) $cart['count'] ?></span>
            </a>
            <a href="<?= e(asset_url('orders.php')) ?>" class="hover:text-indigo-700" data-testid="link-orders">Orders</a>
            <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
                <a href="<?= e(asset_url('admin.php')) ?>" class="hover:text-indigo-700" data-testid="link-admin">Admin</a>
            <?php endif; ?>
            <?php if ($user): ?>
                <span class="text-slate-500" data-testid="label-username"><?= e($user['username']) ?></span>
                <button type="button" id="btn-logout" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-100" data-testid="btn-logout">Logout</button>
            <?php else: ?>
                <a href="<?= e(asset_url('login.php')) ?>" class="rounded-md bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700" data-testid="link-login">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="mx-auto max-w-6xl px-4 py-8" data-testid="main-content">
    <?php
}

function render_footer(): void
{
    ?>
</main>
<footer class="mt-12 border-t border-slate-200 bg-white" data-testid="site-footer">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-slate-500 sm:flex-row">
        <p data-testid="footer-copy">QADemo Shop · PHP 8 test automation sandbox</p>
        <button type="button" id="btn-reset-state" class="rounded-md border border-rose-300 px-3 py-1.5 font-medium text-rose-700 hover:bg-rose-50" data-testid="btn-reset-state">Reset Application State</button>
    </div>
</footer>
<script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>
    <?php
}

function stock_badge(int $stock): string
{
    if ($stock <= 0) {
        return '<span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700" data-testid="badge-stock" data-stock="0">Out of stock</span>';
    }
    if ($stock <= 5) {
        return '<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800" data-testid="badge-stock" data-stock="' . $stock . '">Low stock (' . $stock . ')</span>';
    }
    return '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800" data-testid="badge-stock" data-stock="' . $stock . '">In stock (' . $stock . ')</span>';
}
