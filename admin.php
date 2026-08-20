<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = session_user();
if ($user === null || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . asset_url('login.php') . '?redirect=admin.php');
    exit;
}

$orders = json_read('orders.json');
$products = json_read('products.json');
$users = json_read('users.json');
$revenue = 0.0;
foreach ($orders as $order) {
    $revenue += (float) $order['total'];
}

render_header('Admin dashboard', 'admin');
?>
<h1 class="mb-6 text-3xl font-bold" data-testid="page-title">Admin dashboard</h1>
<section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-testid="admin-metrics">
    <article class="rounded-2xl bg-white p-4 shadow-sm" data-testid="metric-orders">
        <p class="text-sm text-slate-500">Total orders</p>
        <p class="text-2xl font-bold" data-testid="metric-orders-value"><?= count($orders) ?></p>
    </article>
    <article class="rounded-2xl bg-white p-4 shadow-sm" data-testid="metric-revenue">
        <p class="text-sm text-slate-500">Revenue</p>
        <p class="text-2xl font-bold" data-testid="metric-revenue-value">$<?= e(money($revenue)) ?></p>
    </article>
    <article class="rounded-2xl bg-white p-4 shadow-sm" data-testid="metric-products">
        <p class="text-sm text-slate-500">Products</p>
        <p class="text-2xl font-bold" data-testid="metric-products-value"><?= count($products) ?></p>
    </article>
    <article class="rounded-2xl bg-white p-4 shadow-sm" data-testid="metric-users">
        <p class="text-sm text-slate-500">Users</p>
        <p class="text-2xl font-bold" data-testid="metric-users-value"><?= count($users) ?></p>
    </article>
</section>

<section class="mb-10 rounded-2xl border border-slate-200 bg-white p-6" data-testid="admin-products">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-semibold">Inventory CRUD</h2>
        <button type="button" id="btn-add-product" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white" data-testid="btn-add-product">Add product</button>
    </div>
    <form id="form-product" class="mb-6 hidden grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2" data-testid="form-product">
        <input type="hidden" id="input-product-id" data-testid="input-product-id">
        <label class="text-sm">Name <input class="mt-1 w-full rounded border px-3 py-2" id="input-product-name" data-testid="input-product-name" required></label>
        <label class="text-sm">Category <input class="mt-1 w-full rounded border px-3 py-2" id="input-product-category" data-testid="input-product-category"></label>
        <label class="text-sm">Price <input type="number" step="0.01" class="mt-1 w-full rounded border px-3 py-2" id="input-product-price" data-testid="input-product-price"></label>
        <label class="text-sm">Stock <input type="number" class="mt-1 w-full rounded border px-3 py-2" id="input-product-stock" data-testid="input-product-stock"></label>
        <label class="text-sm">SKU <input class="mt-1 w-full rounded border px-3 py-2" id="input-product-sku" data-testid="input-product-sku"></label>
        <label class="text-sm sm:col-span-2">Description <textarea class="mt-1 w-full rounded border px-3 py-2" id="input-product-description" data-testid="input-product-description"></textarea></label>
        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-white" data-testid="btn-save-product">Save product</button>
            <button type="button" id="btn-cancel-product" class="rounded-lg border px-4 py-2" data-testid="btn-cancel-product">Cancel</button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm" data-testid="table-products">
            <thead class="border-b text-slate-500">
                <tr><th class="py-2">ID</th><th>Name</th><th>Stock</th><th>Price</th><th>Actions</th></tr>
            </thead>
            <tbody id="tbody-products" data-testid="tbody-products">
                <?php foreach ($products as $product): ?>
                    <tr class="border-b" data-testid="admin-product-row-<?= (int) $product['id'] ?>">
                        <td class="py-2" data-testid="admin-product-id"><?= (int) $product['id'] ?></td>
                        <td data-testid="admin-product-name"><?= e($product['name']) ?></td>
                        <td>
                            <input type="number" min="0" value="<?= (int) $product['stock'] ?>" class="w-20 rounded border px-2 py-1" data-testid="input-stock-<?= (int) $product['id'] ?>">
                            <button type="button" class="ml-1 text-indigo-700" data-testid="btn-update-stock" data-product-id="<?= (int) $product['id'] ?>">Update</button>
                        </td>
                        <td data-testid="admin-product-price">$<?= e(money($product['price'])) ?></td>
                        <td class="space-x-2">
                            <button type="button" class="text-indigo-700" data-testid="btn-edit-product" data-product-id="<?= (int) $product['id'] ?>">Edit</button>
                            <button type="button" class="text-rose-700" data-testid="btn-delete-product" data-product-id="<?= (int) $product['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6" data-testid="admin-orders">
    <h2 class="mb-4 text-xl font-semibold">Order status</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm" data-testid="table-orders">
            <thead class="border-b text-slate-500">
                <tr><th class="py-2">Order</th><th>Customer</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr class="border-b" data-testid="admin-order-row-<?= (int) $order['id'] ?>">
                        <td class="py-2">#<?= (int) $order['id'] ?></td>
                        <td><?= e($order['username']) ?></td>
                        <td>$<?= e(money($order['total'])) ?></td>
                        <td>
                            <select class="rounded border px-2 py-1" data-testid="select-order-status-<?= (int) $order['id'] ?>" data-order-id="<?= (int) $order['id'] ?>">
                                <?php foreach (['processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                                    <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="ml-2 text-indigo-700" data-testid="btn-update-order-<?= (int) $order['id'] ?>" data-order-id="<?= (int) $order['id'] ?>">Save</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>
