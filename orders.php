<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = session_user();
if ($user === null) {
    header('Location: ' . asset_url('login.php') . '?redirect=orders.php');
    exit;
}
$orders = json_read('orders.json');
if (($user['role'] ?? '') !== 'admin') {
    $orders = array_values(array_filter($orders, static fn ($o) => (int) $o['user_id'] === (int) $user['id']));
}
usort($orders, static fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
render_header('Orders', 'orders');
?>
<h1 class="mb-6 text-3xl font-bold" data-testid="page-title">Order history</h1>
<?php if ($orders === []): ?>
    <p class="rounded-xl bg-white p-6 text-slate-600" data-testid="orders-empty">You have no orders yet.</p>
<?php else: ?>
    <div class="space-y-4" data-testid="orders-list">
        <?php foreach ($orders as $order): ?>
            <article class="rounded-2xl border border-slate-200 bg-white p-5" data-testid="order-card-<?= (int) $order['id'] ?>">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold" data-testid="order-id">Order #<?= (int) $order['id'] ?></h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase" data-testid="order-status"><?= e($order['status']) ?></span>
                </div>
                <p class="mt-1 text-sm text-slate-500" data-testid="order-date"><?= e($order['created_at']) ?></p>
                <ul class="mt-3 space-y-1 text-sm" data-testid="order-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <li><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?> — $<?= e(money((float) $item['price'] * (int) $item['quantity'])) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-3 font-bold" data-testid="order-total">Total $<?= e(money($order['total'])) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php render_footer(); ?>
