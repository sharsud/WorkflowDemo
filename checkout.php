<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = session_user();
if ($user === null) {
    header('Location: ' . asset_url('login.php') . '?redirect=checkout.php');
    exit;
}
$cart = enrich_cart(read_cart());
render_header('Checkout', 'checkout');
?>
<h1 class="mb-6 text-3xl font-bold" data-testid="page-title">Checkout</h1>
<?php if ($cart['items'] === []): ?>
    <p class="rounded-xl bg-white p-6" data-testid="checkout-empty">Your cart is empty. <a class="text-indigo-700 underline" href="<?= e(asset_url('index.php')) ?>" data-testid="link-continue-shopping">Return to catalog</a>.</p>
<?php else: ?>
<div class="grid gap-8 lg:grid-cols-5">
    <form id="form-checkout" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-3" data-testid="form-checkout" novalidate>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm">Full name
                <input type="text" name="name" id="input-fullname" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-fullname" value="<?= e($user['name'] ?? '') ?>">
                <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-fullname"></span>
            </label>
            <label class="text-sm">Email
                <input type="email" name="email" id="input-email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-email" value="<?= e($user['email'] ?? '') ?>">
                <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-email"></span>
            </label>
        </div>
        <label class="block text-sm">Address
            <input type="text" name="address" id="input-address" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-address">
            <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-address"></span>
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm">City
                <input type="text" name="city" id="input-city" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-city">
                <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-city"></span>
            </label>
            <label class="text-sm">ZIP
                <input type="text" name="zip" id="input-zip" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-zip">
                <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-zip"></span>
            </label>
        </div>
        <label class="block text-sm">Card number
            <input type="text" name="card" id="input-card" inputmode="numeric" autocomplete="cc-number" maxlength="19" placeholder="4242 4242 4242 4242" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-card">
            <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-card"></span>
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm">Expiry (MM/YY)
                <input type="text" name="expiry" id="input-expiry" maxlength="5" placeholder="12/28" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-expiry">
                <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-expiry"></span>
            </label>
            <label class="text-sm">CVV
                <input type="text" name="cvv" id="input-cvv" maxlength="4" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" data-testid="input-cvv">
                <span class="mt-1 hidden text-xs text-rose-600" data-testid="error-cvv"></span>
            </label>
        </div>
        <button type="button" id="btn-fill-test-card" class="text-sm text-indigo-700 hover:underline" data-testid="btn-fill-test-card">Fill test card 4242 4242 4242 4242</button>
        <p id="checkout-error" class="hidden rounded-lg bg-rose-50 p-3 text-sm text-rose-700" data-testid="checkout-error"></p>
        <button type="submit" id="btn-place-order" class="w-full rounded-lg bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-700" data-testid="btn-place-order">Place order</button>
    </form>
    <aside class="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2" data-testid="checkout-summary">
        <h2 class="mb-4 font-semibold">Order summary</h2>
        <ul class="space-y-2 text-sm" data-testid="checkout-items">
            <?php foreach ($cart['items'] as $item): ?>
                <li class="flex justify-between" data-testid="checkout-item-<?= (int) $item['product_id'] ?>">
                    <span><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></span>
                    <span>$<?= e(money($item['line_total'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="mt-4 border-t pt-4 text-lg font-bold" data-testid="checkout-total">Total $<?= e(money($cart['subtotal'])) ?></p>
        <p class="mt-3 text-xs text-slate-500">Checkout waits 1.2s after submit so WebDriverWait can target #loading-spinner.</p>
    </aside>
</div>
<div id="checkout-success" class="hidden mt-6 rounded-xl bg-emerald-50 p-6 text-emerald-800" data-testid="checkout-success"></div>
<?php endif; ?>
<?php render_footer(); ?>
