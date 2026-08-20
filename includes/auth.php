<?php
declare(strict_types=1);

function users_all(): array
{
    return json_read('users.json');
}

function find_user_by_username(string $username): ?array
{
    foreach (users_all() as $user) {
        if (strcasecmp((string) ($user['username'] ?? ''), $username) === 0) {
            return $user;
        }
    }
    return null;
}

function public_user(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'locked' => (bool) $user['locked'],
        'name' => $user['name'] ?? $user['username'],
        'email' => $user['email'] ?? '',
    ];
}

function password_matches(array $user, string $password): bool
{
    if (password_verify($password, (string) ($user['password'] ?? ''))) {
        return true;
    }
    foreach ($user['aliases'] ?? [] as $alias) {
        if (hash_equals((string) $alias, $password)) {
            return true;
        }
    }
    return false;
}

function authorization_header(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'AUTHORIZATION'] as $key) {
        if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
            return $_SERVER[$key];
        }
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                return (string) $value;
            }
        }
    }
    return '';
}

function basic_credentials(): ?array
{
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        return [(string) $_SERVER['PHP_AUTH_USER'], (string) ($_SERVER['PHP_AUTH_PW'] ?? '')];
    }

    $header = authorization_header();
    if (stripos($header, 'Basic ') !== 0) {
        return null;
    }

    $decoded = base64_decode(trim(substr($header, 6)), true);
    if ($decoded === false || !str_contains($decoded, ':')) {
        return null;
    }

    [$user, $pass] = explode(':', $decoded, 2);
    return [$user, $pass];
}

function authenticate_credentials(string $username, string $password): array
{
    $user = find_user_by_username($username);
    if ($user === null || !password_matches($user, $password)) {
        return ['ok' => false, 'status' => 401, 'error' => 'Invalid username or password.'];
    }
    if (!empty($user['locked'])) {
        return ['ok' => false, 'status' => 403, 'error' => 'Account is locked.', 'code' => 'locked'];
    }
    return ['ok' => true, 'user' => $user];
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    merge_guest_cart($user['username']);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}

function session_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $user = find_by_id(users_all(), (int) $_SESSION['user_id']);
    if ($user === null || !empty($user['locked'])) {
        return null;
    }
    return $user;
}

function current_user(): ?array
{
    $session = session_user();
    if ($session !== null) {
        return $session;
    }

    $basic = basic_credentials();
    if ($basic === null) {
        return null;
    }

    $result = authenticate_credentials($basic[0], $basic[1]);
    if (empty($result['ok'])) {
        return null;
    }
    return $result['user'];
}

function require_user(): array
{
    $user = current_user();
    if ($user === null) {
        $basic = basic_credentials();
        if ($basic !== null) {
            $result = authenticate_credentials($basic[0], $basic[1]);
            if (empty($result['ok'])) {
                json_error($result['error'], $result['status'], isset($result['code']) ? ['code' => $result['code']] : []);
            }
        }
        json_error('Authentication required.', 401);
    }
    return $user;
}

function require_admin(): array
{
    $user = require_user();
    if (($user['role'] ?? '') !== 'admin') {
        json_error('Admin access required.', 403);
    }
    return $user;
}

function cart_key(?array $user = null): string
{
    $user = $user ?? current_user();
    if ($user !== null) {
        return (string) $user['username'];
    }
    return 'guest_' . session_id();
}

function read_cart(?string $key = null): array
{
    $carts = json_read('carts.json');
    $key = $key ?? cart_key();
    $cart = $carts[$key] ?? ['items' => []];
    if (!isset($cart['items']) || !is_array($cart['items'])) {
        $cart['items'] = [];
    }
    return $cart;
}

function write_cart(array $cart, ?string $key = null): void
{
    $carts = json_read('carts.json');
    $key = $key ?? cart_key();
    $carts[$key] = $cart;
    json_write('carts.json', $carts);
}

function merge_guest_cart(string $username): void
{
    $guestKey = 'guest_' . session_id();
    $carts = json_read('carts.json');
    $guest = $carts[$guestKey]['items'] ?? [];
    if ($guest === []) {
        return;
    }
    $userCart = $carts[$username]['items'] ?? [];
    foreach ($guest as $item) {
        $found = false;
        foreach ($userCart as &$existing) {
            if ((int) $existing['product_id'] === (int) $item['product_id']) {
                $existing['quantity'] = (int) $existing['quantity'] + (int) $item['quantity'];
                $found = true;
                break;
            }
        }
        unset($existing);
        if (!$found) {
            $userCart[] = $item;
        }
    }
    $carts[$username] = ['items' => $userCart];
    unset($carts[$guestKey]);
    json_write('carts.json', $carts);
}

function enrich_cart(array $cart): array
{
    $products = json_read('products.json');
    $items = [];
    $subtotal = 0.0;
    $count = 0;
    foreach ($cart['items'] as $line) {
        $product = find_by_id($products, (int) $line['product_id']);
        if ($product === null) {
            continue;
        }
        $qty = max(1, (int) $line['quantity']);
        $lineTotal = (float) $product['price'] * $qty;
        $subtotal += $lineTotal;
        $count += $qty;
        $items[] = [
            'product_id' => (int) $product['id'],
            'name' => $product['name'],
            'price' => (float) $product['price'],
            'quantity' => $qty,
            'stock' => (int) $product['stock'],
            'image' => $product['image'],
            'line_total' => round($lineTotal, 2),
        ];
    }
    return [
        'items' => $items,
        'count' => $count,
        'subtotal' => round($subtotal, 2),
    ];
}
