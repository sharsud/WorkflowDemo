<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$product = find_by_id(json_read('products.json'), $id);
if ($product === null) {
    http_response_code(404);
    render_header('Product not found', 'product');
    echo '<p class="rounded-lg bg-white p-6 text-slate-600" data-testid="product-not-found">Product not found.</p>';
    render_footer();
    exit;
}
$inStock = (int) $product['stock'] > 0;
render_header($product['name'], 'product');
?>
<a href="<?= e(asset_url('index.php')) ?>" class="mb-6 inline-block text-sm text-indigo-700 hover:underline" data-testid="link-back-catalog">← Back to catalog</a>
<div class="grid gap-8 rounded-2xl border border-slate-200 bg-white p-6 md:grid-cols-2" data-testid="product-detail" data-product-id="<?= (int) $product['id'] ?>">
    <img src="<?= e(asset_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="h-72 w-full rounded-xl object-cover" data-testid="product-image">
    <div>
        <p class="text-sm uppercase tracking-wide text-slate-500" data-testid="product-category"><?= e($product['category']) ?></p>
        <h1 class="mt-1 text-3xl font-bold" data-testid="product-title"><?= e($product['name']) ?></h1>
        <div class="mt-3"><?= stock_badge((int) $product['stock']) ?></div>
        <p class="mt-4 text-2xl font-bold text-indigo-700" data-testid="product-price">$<?= e(money($product['price'])) ?></p>
        <p class="mt-2 text-sm text-slate-500" data-testid="product-sku">SKU <?= e($product['sku']) ?></p>
        <p class="mt-4 text-slate-600" data-testid="product-description"><?= e($product['description']) ?></p>
        <div class="mt-6 flex items-center gap-3">
            <label class="text-sm">
                <span class="mb-1 block text-slate-600">Qty</span>
                <input type="number" id="input-qty" min="1" max="<?= (int) $product['stock'] ?>" value="1" class="w-20 rounded-lg border border-slate-300 px-3 py-2" data-testid="input-qty" <?= $inStock ? '' : 'disabled' ?>>
            </label>
            <button type="button" class="mt-6 rounded-lg bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700 disabled:bg-slate-300" data-testid="btn-add-to-cart" data-product-id="<?= (int) $product['id'] ?>" <?= $inStock ? '' : 'disabled' ?>>Add to Cart</button>
        </div>
    </div>
</div>
<?php render_footer(); ?>
