<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$products = json_read('products.json');
$categories = array_values(array_unique(array_map(static fn ($p) => $p['category'], $products)));
sort($categories);

render_header('Catalog', 'catalog');
?>
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-3xl font-bold text-slate-900" data-testid="page-title">Product catalog</h1>
        <p class="mt-1 text-slate-600" data-testid="page-subtitle">Filter by category or search. Stock badges update from live JSON data.</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row">
        <label class="block text-sm">
            <span class="mb-1 block text-slate-600">Search</span>
            <input type="search" id="input-search" name="q" placeholder="Search products" class="w-64 rounded-lg border border-slate-300 px-3 py-2" data-testid="input-search">
        </label>
        <label class="block text-sm">
            <span class="mb-1 block text-slate-600">Category</span>
            <select id="select-category" name="category" class="w-48 rounded-lg border border-slate-300 px-3 py-2" data-testid="select-category">
                <option value="" data-testid="option-category-all">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>" data-testid="option-category-<?= e(strtolower($category)) ?>"><?= e($category) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
</div>
<p id="catalog-empty" class="hidden rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500" data-testid="catalog-empty">No products match your filters.</p>
<div id="product-grid" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-testid="product-grid">
    <?php foreach ($products as $product): ?>
        <?php $inStock = (int) $product['stock'] > 0; ?>
        <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" data-testid="product-card-<?= (int) $product['id'] ?>" data-product-id="<?= (int) $product['id'] ?>" data-category="<?= e($product['category']) ?>" data-name="<?= e(strtolower($product['name'])) ?>">
            <img src="<?= e(asset_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="mb-4 h-40 w-full rounded-xl object-cover" data-testid="product-image">
            <div class="mb-2 flex items-start justify-between gap-2">
                <h2 class="font-semibold text-slate-900" data-testid="product-title"><?= e($product['name']) ?></h2>
                <?= stock_badge((int) $product['stock']) ?>
            </div>
            <p class="mb-1 text-xs uppercase tracking-wide text-slate-500" data-testid="product-category"><?= e($product['category']) ?></p>
            <p class="mb-4 text-lg font-bold text-indigo-700" data-testid="product-price">$<?= e(money($product['price'])) ?></p>
            <div class="mt-auto flex gap-2">
                <a href="<?= e(asset_url('product.php?id=' . (int) $product['id'])) ?>" class="flex-1 rounded-lg border border-slate-300 py-2 text-center text-sm hover:bg-slate-50" data-testid="btn-view-product">View</a>
                <button type="button" class="flex-1 rounded-lg bg-indigo-600 py-2 text-sm text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300" data-testid="btn-add-to-cart" data-product-id="<?= (int) $product['id'] ?>" <?= $inStock ? '' : 'disabled' ?>>Add to Cart</button>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php render_footer(); ?>
