<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/api_handlers.php';

$route = isset($_GET['route']) && is_string($_GET['route']) ? $_GET['route'] : api_route_from_request();
if ($route === '' || $route === 'index') {
    json_response([
        'ok' => true,
        'name' => 'QADemo Shop API',
        'endpoints' => [
            'GET /api/products.php',
            'GET /api/products.php?id={id}',
            'POST /api/auth/login.php',
            'POST /api/auth/logout.php',
            'GET /api/auth/me.php',
            'GET /api/cart.php',
            'POST /api/cart/items.php',
            'DELETE /api/cart/items.php',
            'GET /api/orders.php',
            'POST /api/orders.php',
            'GET /api/admin/stats.php',
            'GET /api/admin/products.php',
            'PATCH /api/admin/products.php',
            'GET /api/admin/orders.php',
            'PATCH /api/admin/orders.php',
            'POST /api/reset.php',
        ],
    ]);
}
api_dispatch($route);
