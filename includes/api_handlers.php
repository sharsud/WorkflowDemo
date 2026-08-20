<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

function api_dispatch(?string $forced = null): never
{
    $method = request_method();
    $route = $forced ?? api_route_from_request();
    $route = trim($route, '/');

    $handlers = [
        'GET products' => 'handle_get_products',
        'POST auth/login' => 'handle_login',
        'POST auth/logout' => 'handle_logout',
        'GET auth/me' => 'handle_me',
        'GET cart' => 'handle_get_cart',
        'POST cart/items' => 'handle_post_cart_item',
        'DELETE cart/items' => 'handle_delete_cart_item',
        'GET orders' => 'handle_get_orders',
        'POST orders' => 'handle_post_order',
        'GET admin/stats' => 'handle_admin_stats',
        'GET admin/products' => 'handle_admin_get_products',
        'POST admin/products' => 'handle_admin_create_product',
        'PATCH admin/products' => 'handle_admin_patch_product',
        'DELETE admin/products' => 'handle_admin_delete_product',
        'GET admin/orders' => 'handle_admin_get_orders',
        'PATCH admin/orders' => 'handle_admin_patch_order',
        'POST reset' => 'handle_reset',
        'GET reset' => 'handle_reset',
    ];

    $key = $method . ' ' . $route;
    if ($route === 'reset.php' || $route === 'reset') {
        handle_reset();
    }

    if (!isset($handlers[$key])) {
        json_error('Route not found.', 404, ['method' => $method, 'route' => $route]);
    }

    $handlers[$key]();
    json_error('Unhandled route.', 500);
}

function api_route_from_request(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $uri = rawurldecode($uri);
    $base = app_base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = trim($uri, '/');

    if (isset($_GET['route']) && is_string($_GET['route'])) {
        return trim($_GET['route'], '/');
    }

    if (preg_match('#(?:^|/)api(?:\.php)?(?:/|$)(.*)$#', $uri, $m)) {
        $rest = trim($m[1], '/');
        $rest = preg_replace('/\.php$/', '', $rest) ?? $rest;
        return $rest === '' ? 'index' : $rest;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#/api/(.+)$#', $script, $m)) {
        return preg_replace('/\.php$/', '', $m[1]) ?? $m[1];
    }

    return '';
}

function handle_get_products(): never
{
    $products = json_read('products.json');
    $id = $_GET['id'] ?? null;
    if ($id !== null && $id !== '') {
        $product = find_by_id($products, (int) $id);
        if ($product === null) {
            json_error('Product not found.', 404);
        }
        json_response(['ok' => true, 'product' => $product]);
    }
    $category = trim((string) ($_GET['category'] ?? ''));
    $q = trim((string) ($_GET['q'] ?? ''));
    $filtered = array_values(array_filter($products, static function (array $p) use ($category, $q): bool {
        if ($category !== '' && strcasecmp((string) $p['category'], $category) !== 0) {
            return false;
        }
        if ($q !== '') {
            $hay = strtolower($p['name'] . ' ' . $p['description'] . ' ' . $p['sku']);
            if (!str_contains($hay, strtolower($q))) {
                return false;
            }
        }
        return true;
    }));
    json_response(['ok' => true, 'products' => $filtered]);
}

function handle_login(): never
{
    $body = request_json();
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    if ($username === '' || $password === '') {
        json_error('Username and password are required.', 400);
    }
    $result = authenticate_credentials($username, $password);
    append_log('login_attempt', ['username' => $username, 'ok' => !empty($result['ok'])]);
    if (empty($result['ok'])) {
        json_error($result['error'], $result['status'], isset($result['code']) ? ['code' => $result['code']] : []);
    }
    login_user($result['user']);
    json_response(['ok' => true, 'user' => public_user($result['user'])]);
}

function handle_logout(): never
{
    logout_user();
    json_response(['ok' => true, 'message' => 'Logged out.']);
}

function handle_me(): never
{
    $user = current_user();
    if ($user === null) {
        json_error('Authentication required.', 401);
    }
    json_response(['ok' => true, 'user' => public_user($user)]);
}

function handle_get_cart(): never
{
    json_response(['ok' => true, 'cart' => enrich_cart(read_cart())]);
}

function handle_post_cart_item(): never
{
    $body = request_json();
    if (!empty($body['clear']) || (isset($body['action']) && in_array(strtolower((string) $body['action']), ['delete', 'remove'], true))) {
        handle_delete_cart_item();
    }
    $productId = (int) ($body['product_id'] ?? $body['id'] ?? 0);
    $quantity = (int) ($body['quantity'] ?? 1);
    if ($productId < 1) {
        json_error('product_id is required.', 400);
    }
    $set = array_key_exists('set', $body) ? (bool) $body['set'] : false;
    $products = json_read('products.json');
    $product = find_by_id($products, $productId);
    if ($product === null) {
        json_error('Product not found.', 404);
    }
    if ((int) $product['stock'] <= 0) {
        json_error('Product is out of stock.', 400);
    }

    $cart = read_cart();
    $found = false;
    foreach ($cart['items'] as &$item) {
        if ((int) $item['product_id'] === $productId) {
            $item['quantity'] = $set ? max(1, $quantity) : max(1, (int) $item['quantity'] + max(1, $quantity));
            if ($item['quantity'] > (int) $product['stock']) {
                json_error('Requested quantity exceeds stock.', 400);
            }
            $found = true;
            break;
        }
    }
    unset($item);
    if (!$found) {
        $qty = max(1, $quantity);
        if ($qty > (int) $product['stock']) {
            json_error('Requested quantity exceeds stock.', 400);
        }
        $cart['items'][] = ['product_id' => $productId, 'quantity' => $qty];
    }
    write_cart($cart);
    json_response(['ok' => true, 'cart' => enrich_cart(read_cart())]);
}

function handle_delete_cart_item(): never
{
    $body = request_json();
    $clear = !empty($body['clear']) || (($_GET['clear'] ?? '') === '1');
    if ($clear) {
        write_cart(['items' => []]);
        json_response(['ok' => true, 'cart' => enrich_cart(read_cart())]);
    }
    $productId = (int) ($body['product_id'] ?? $body['id'] ?? $_GET['product_id'] ?? $_GET['id'] ?? 0);
    if ($productId < 1) {
        json_error('product_id is required.', 400);
    }
    $cart = read_cart();
    $cart['items'] = array_values(array_filter($cart['items'], static fn ($item) => (int) $item['product_id'] !== $productId));
    write_cart($cart);
    json_response(['ok' => true, 'cart' => enrich_cart(read_cart())]);
}

function handle_get_orders(): never
{
    $user = require_user();
    $orders = json_read('orders.json');
    if (($user['role'] ?? '') !== 'admin') {
        $orders = array_values(array_filter($orders, static fn ($o) => (int) $o['user_id'] === (int) $user['id']));
    }
    json_response(['ok' => true, 'orders' => $orders]);
}

function luhn_valid(string $number): bool
{
    $number = preg_replace('/\D/', '', $number) ?? '';
    if ($number === '') {
        return false;
    }
    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = (int) $number[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    return $sum % 10 === 0;
}

function handle_post_order(): never
{
    $user = require_user();
    $body = request_json();
    $name = trim((string) ($body['name'] ?? $body['fullname'] ?? ''));
    $email = trim((string) ($body['email'] ?? ''));
    $address = trim((string) ($body['address'] ?? ''));
    $city = trim((string) ($body['city'] ?? ''));
    $zip = trim((string) ($body['zip'] ?? ''));
    $card = preg_replace('/\D/', '', (string) ($body['card'] ?? $body['card_number'] ?? '')) ?? '';
    $expiry = trim((string) ($body['expiry'] ?? ''));
    $cvv = trim((string) ($body['cvv'] ?? ''));

    $errors = [];
    if ($name === '') $errors['name'] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'A valid email is required.';
    if ($address === '') $errors['address'] = 'Address is required.';
    if ($city === '') $errors['city'] = 'City is required.';
    if ($zip === '') $errors['zip'] = 'ZIP is required.';
    if (strlen($card) < 13 || !luhn_valid($card)) $errors['card'] = 'Enter a valid card number.';
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) $errors['expiry'] = 'Expiry must be MM/YY.';
    if (!preg_match('/^\d{3,4}$/', $cvv)) $errors['cvv'] = 'CVV must be 3 or 4 digits.';
    if ($errors !== []) {
        json_error('Validation failed.', 400, ['fields' => $errors]);
    }

    $cart = enrich_cart(read_cart($user['username']));
    if ($cart['items'] === []) {
        json_error('Cart is empty.', 400);
    }

    $products = json_read('products.json');
    foreach ($cart['items'] as $line) {
        $product = find_by_id($products, (int) $line['product_id']);
        if ($product === null || (int) $product['stock'] < (int) $line['quantity']) {
            json_error('Insufficient stock for ' . ($product['name'] ?? 'item') . '.', 400);
        }
    }
    foreach ($cart['items'] as $line) {
        foreach ($products as &$product) {
            if ((int) $product['id'] === (int) $line['product_id']) {
                $product['stock'] = (int) $product['stock'] - (int) $line['quantity'];
            }
        }
        unset($product);
    }
    json_write('products.json', $products);

    $orders = json_read('orders.json');
    $nextId = 1001;
    foreach ($orders as $order) {
        $nextId = max($nextId, (int) $order['id'] + 1);
    }
    $order = [
        'id' => $nextId,
        'user_id' => (int) $user['id'],
        'username' => $user['username'],
        'status' => 'processing',
        'total' => $cart['subtotal'],
        'items' => array_map(static fn ($i) => [
            'product_id' => $i['product_id'],
            'name' => $i['name'],
            'price' => $i['price'],
            'quantity' => $i['quantity'],
        ], $cart['items']),
        'shipping' => compact('name', 'email', 'address', 'city', 'zip'),
        'payment' => ['brand' => 'visa', 'last4' => substr($card, -4)],
        'created_at' => now_iso(),
    ];
    $orders[] = $order;
    json_write('orders.json', $orders);
    write_cart(['items' => []], $user['username']);
    append_log('order_placed', ['order_id' => $nextId, 'username' => $user['username']]);
    json_response(['ok' => true, 'order' => $order], 200);
}

function handle_admin_stats(): never
{
    require_admin();
    $orders = json_read('orders.json');
    $products = json_read('products.json');
    $users = json_read('users.json');
    $revenue = 0.0;
    $statusCounts = [];
    foreach ($orders as $order) {
        $revenue += (float) $order['total'];
        $status = (string) $order['status'];
        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    }
    $lowStock = array_values(array_filter($products, static fn ($p) => (int) $p['stock'] <= 5));
    json_response([
        'ok' => true,
        'stats' => [
            'total_orders' => count($orders),
            'revenue' => round($revenue, 2),
            'product_count' => count($products),
            'user_count' => count($users),
            'low_stock_count' => count($lowStock),
            'status_counts' => $statusCounts,
        ],
    ]);
}

function handle_admin_get_products(): never
{
    require_admin();
    json_response(['ok' => true, 'products' => json_read('products.json')]);
}

function next_product_id(array $products): int
{
    $max = 0;
    foreach ($products as $p) {
        $max = max($max, (int) $p['id']);
    }
    return $max + 1;
}

function handle_admin_create_product(): never
{
    require_admin();
    $body = request_json();
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        json_error('Product name is required.', 400);
    }
    $products = json_read('products.json');
    $id = next_product_id($products);
    $product = [
        'id' => $id,
        'name' => $name,
        'slug' => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? $name, '-')),
        'category' => trim((string) ($body['category'] ?? 'Accessories')) ?: 'Accessories',
        'price' => round((float) ($body['price'] ?? 0), 2),
        'stock' => max(0, (int) ($body['stock'] ?? 0)),
        'sku' => trim((string) ($body['sku'] ?? 'SKU-' . $id)),
        'description' => trim((string) ($body['description'] ?? '')),
        'image' => trim((string) ($body['image'] ?? 'assets/img/product-1.svg')),
    ];
    $products[] = $product;
    json_write('products.json', $products);
    json_response(['ok' => true, 'product' => $product], 200);
}

function handle_admin_patch_product(): never
{
    require_admin();
    $body = request_json();
    $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    $products = json_read('products.json');
    $product = find_by_id($products, $id);
    if ($product === null) {
        json_error('Product not found.', 404);
    }
    foreach (['name', 'category', 'sku', 'description', 'image'] as $field) {
        if (array_key_exists($field, $body)) {
            $product[$field] = $body[$field];
        }
    }
    if (array_key_exists('price', $body)) {
        $product['price'] = round((float) $body['price'], 2);
    }
    if (array_key_exists('stock', $body)) {
        $product['stock'] = max(0, (int) $body['stock']);
    }
    json_write('products.json', replace_by_id($products, $product));
    json_response(['ok' => true, 'product' => $product]);
}

function handle_admin_delete_product(): never
{
    require_admin();
    $body = request_json();
    $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    $products = json_read('products.json');
    $exists = find_by_id($products, $id);
    if ($exists === null) {
        json_error('Product not found.', 404);
    }
    $products = array_values(array_filter($products, static fn ($p) => (int) $p['id'] !== $id));
    json_write('products.json', $products);
    json_response(['ok' => true, 'deleted' => $id]);
}

function handle_admin_get_orders(): never
{
    require_admin();
    json_response(['ok' => true, 'orders' => json_read('orders.json')]);
}

function handle_admin_patch_order(): never
{
    require_admin();
    $body = request_json();
    $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    $status = strtolower(trim((string) ($body['status'] ?? '')));
    $allowed = ['processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        json_error('Invalid status. Use processing, shipped, delivered, or cancelled.', 400);
    }
    $orders = json_read('orders.json');
    $order = find_by_id($orders, $id);
    if ($order === null) {
        json_error('Order not found.', 404);
    }
    $order['status'] = $status;
    json_write('orders.json', replace_by_id($orders, $order));
    json_response(['ok' => true, 'order' => $order]);
}

function handle_reset(): never
{
    json_response(reset_application_state());
}
