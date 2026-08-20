<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$cart = enrich_cart(read_cart());
render_header('Cart', 'cart');
?>
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-3xl font-bold" data-testid="page-title">Your cart</h1>
    <button type="button" id="btn-clear-cart" class="rounded-lg border border-rose-300 px-3 py-2 text-sm text-rose-700 hover:bg-rose-50 disabled:opacity-40" data-testid="btn-clear-cart" <?= $cart['items'] === [] ? 'disabled' : '' ?>>Clear cart</button>
</div>
<div id="cart-empty" class="<?= $cart['items'] === [] ? '' : 'hidden' ?> rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center" data-testid="cart-empty">
    <p class="text-slate-600">Your cart is empty.</p>
    <a href="<?= e(asset_url('index.php')) ?>" class="mt-4 inline-block text-indigo-700 hover:underline" data-testid="link-continue-shopping">Continue shopping</a>
</div>
<div id="cart-content" class="<?= $cart['items'] === [] ? 'hidden' : '' ?>" data-testid="cart-content">
    <div id="cart-items" class="space-y-4" data-testid="cart-items">
        <?php foreach ($cart['items'] as $item): ?>
            <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center" data-testid="cart-item-<?= (int) $item['product_id'] ?>">
                <img src="<?= e(asset_url($item['image'])) ?>" alt="" class="h-20 w-20 rounded-lg object-cover">
                <div class="flex-1">
                    <h2 class="font-semibold" data-testid="cart-item-name"><?= e($item['name']) ?></h2>
                    <p class="text-sm text-slate-500" data-testid="cart-item-price">$<?= e(money($item['price'])) ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="h-8 w-8 rounded border border-slate-300" data-testid="btn-qty-decrease" data-product-id="<?= (int) $item['product_id'] ?>">−</button>
                    <input type="number" min="1" max="<?= (int) $item['stock'] ?>" value="<?= (int) $item['quantity'] ?>" class="w-16 rounded border border-slate-300 px-2 py-1 text-center" data-testid="input-qty" data-product-id="<?= (int) $item['product_id'] ?>">
                    <button type="button" class="h-8 w-8 rounded border border-slate-300" data-testid="btn-qty-increase" data-product-id="<?= (int) $item['product_id'] ?>">+</button>
                </div>
                <p class="w-24 text-right font-semibold" data-testid="cart-item-total">$<?= e(money($item['line_total'])) ?></p>
                <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" data-testid="btn-remove-item" data-product-id="<?= (int) $item['product_id'] ?>">Remove</button>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-6 flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
        <p class="text-lg font-semibold" data-testid="cart-subtotal">Subtotal: $<?= e(money($cart['subtotal'])) ?></p>
        <a href="<?= e(asset_url('checkout.php')) ?>" class="rounded-lg bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700" data-testid="btn-checkout">Checkout</a>
    </div>
</div>
<?php render_footer(); ?>
