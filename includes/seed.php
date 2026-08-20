<?php
declare(strict_types=1);

function default_users(): array
{
    return [
        [
            'id' => 1,
            'username' => 'standard_user',
            'password' => password_hash('standard123', PASSWORD_DEFAULT),
            'aliases' => ['password123'],
            'role' => 'user',
            'locked' => false,
            'name' => 'Alex Rivera',
            'email' => 'standard_user@qademo.test',
        ],
        [
            'id' => 2,
            'username' => 'locked_user',
            'password' => password_hash('locked123', PASSWORD_DEFAULT),
            'aliases' => [],
            'role' => 'user',
            'locked' => true,
            'name' => 'Morgan Blake',
            'email' => 'locked_user@qademo.test',
        ],
        [
            'id' => 3,
            'username' => 'admin_user',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'aliases' => [],
            'role' => 'admin',
            'locked' => false,
            'name' => 'Jamie Chen',
            'email' => 'admin_user@qademo.test',
        ],
    ];
}

function default_products(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Nimbus Wireless Headphones',
            'slug' => 'nimbus-wireless-headphones',
            'category' => 'Electronics',
            'price' => 129.99,
            'stock' => 18,
            'sku' => 'EL-HP-001',
            'description' => 'Over-ear Bluetooth headphones with 30-hour battery life, active noise cancellation, and a foldable travel case.',
            'image' => 'assets/img/product-1.svg',
        ],
        [
            'id' => 2,
            'name' => 'Pulse Fitness Watch',
            'slug' => 'pulse-fitness-watch',
            'category' => 'Electronics',
            'price' => 89.50,
            'stock' => 7,
            'sku' => 'EL-WT-002',
            'description' => 'Water-resistant smartwatch with heart-rate tracking, sleep scoring, and a 1.4-inch always-on display.',
            'image' => 'assets/img/product-2.svg',
        ],
        [
            'id' => 3,
            'name' => 'Aero Running Shoes',
            'slug' => 'aero-running-shoes',
            'category' => 'Apparel',
            'price' => 74.00,
            'stock' => 24,
            'sku' => 'AP-SH-003',
            'description' => 'Lightweight mesh runners with responsive foam midsoles. Ideal for daily training and automation demo checkouts.',
            'image' => 'assets/img/product-3.svg',
        ],
        [
            'id' => 4,
            'name' => 'Canyon Backpack',
            'slug' => 'canyon-backpack',
            'category' => 'Accessories',
            'price' => 59.99,
            'stock' => 12,
            'sku' => 'AC-BP-004',
            'description' => '16-inch laptop backpack with padded straps, a hidden zipper pocket, and weather-resistant fabric.',
            'image' => 'assets/img/product-4.svg',
        ],
        [
            'id' => 5,
            'name' => 'Lumen Desk Lamp',
            'slug' => 'lumen-desk-lamp',
            'category' => 'Home',
            'price' => 42.25,
            'stock' => 3,
            'sku' => 'HM-LP-005',
            'description' => 'USB-C desk lamp with three color temperatures and a touch dimmer. Low-stock item for badge testing.',
            'image' => 'assets/img/product-5.svg',
        ],
        [
            'id' => 6,
            'name' => 'Harbor Ceramic Mug',
            'slug' => 'harbor-ceramic-mug',
            'category' => 'Home',
            'price' => 14.99,
            'stock' => 40,
            'sku' => 'HM-MG-006',
            'description' => '12 oz matte ceramic mug. Dishwasher safe and a reliable in-stock catalog fixture.',
            'image' => 'assets/img/product-6.svg',
        ],
        [
            'id' => 7,
            'name' => 'Northwind Parka',
            'slug' => 'northwind-parka',
            'category' => 'Apparel',
            'price' => 159.00,
            'stock' => 0,
            'sku' => 'AP-JK-007',
            'description' => 'Insulated winter parka. Intentionally out of stock so tests can assert disabled Add to Cart states.',
            'image' => 'assets/img/product-7.svg',
        ],
        [
            'id' => 8,
            'name' => 'Orbit USB-C Hub',
            'slug' => 'orbit-usb-c-hub',
            'category' => 'Electronics',
            'price' => 36.00,
            'stock' => 15,
            'sku' => 'EL-HB-008',
            'description' => '7-in-1 USB-C hub with HDMI 4K, SD reader, and 100W pass-through charging.',
            'image' => 'assets/img/product-8.svg',
        ],
        [
            'id' => 9,
            'name' => 'Trail Water Bottle',
            'slug' => 'trail-water-bottle',
            'category' => 'Accessories',
            'price' => 22.50,
            'stock' => 31,
            'sku' => 'AC-BT-009',
            'description' => '24 oz insulated stainless bottle that keeps drinks cold for 24 hours.',
            'image' => 'assets/img/product-9.svg',
        ],
        [
            'id' => 10,
            'name' => 'Studio Notebook Set',
            'slug' => 'studio-notebook-set',
            'category' => 'Home',
            'price' => 18.75,
            'stock' => 2,
            'sku' => 'HM-NB-010',
            'description' => 'Pack of three A5 dotted notebooks. Low stock for inventory CRUD and badge tests.',
            'image' => 'assets/img/product-10.svg',
        ],
    ];
}

function default_orders(): array
{
    return [
        [
            'id' => 1001,
            'user_id' => 1,
            'username' => 'standard_user',
            'status' => 'delivered',
            'total' => 144.98,
            'items' => [
                ['product_id' => 6, 'name' => 'Harbor Ceramic Mug', 'price' => 14.99, 'quantity' => 1],
                ['product_id' => 1, 'name' => 'Nimbus Wireless Headphones', 'price' => 129.99, 'quantity' => 1],
            ],
            'shipping' => [
                'name' => 'Alex Rivera',
                'email' => 'standard_user@qademo.test',
                'address' => '101 Market Street',
                'city' => 'Austin',
                'zip' => '78701',
            ],
            'payment' => ['brand' => 'visa', 'last4' => '4242'],
            'created_at' => '2026-08-01T14:22:00Z',
        ],
        [
            'id' => 1002,
            'user_id' => 1,
            'username' => 'standard_user',
            'status' => 'processing',
            'total' => 74.00,
            'items' => [
                ['product_id' => 3, 'name' => 'Aero Running Shoes', 'price' => 74.00, 'quantity' => 1],
            ],
            'shipping' => [
                'name' => 'Alex Rivera',
                'email' => 'standard_user@qademo.test',
                'address' => '101 Market Street',
                'city' => 'Austin',
                'zip' => '78701',
            ],
            'payment' => ['brand' => 'visa', 'last4' => '4242'],
            'created_at' => '2026-08-12T09:05:00Z',
        ],
    ];
}

function ensure_seed_data(bool $force = false): void
{
    $files = [
        'users.json' => 'default_users',
        'products.json' => 'default_products',
        'orders.json' => 'default_orders',
        'carts.json' => static fn () => [],
        'logs.json' => static fn () => [],
    ];

    foreach ($files as $file => $factory) {
        $path = data_path($file);
        if ($force || !is_file($path) || filesize($path) === 0) {
            $data = is_string($factory) ? $factory() : $factory();
            json_write($file, $data);
        }
    }
}

function reset_application_state(): array
{
    ensure_seed_data(true);
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
    }
    append_log('reset', ['source' => 'api']);
    return [
        'ok' => true,
        'message' => 'Application state restored to seed defaults.',
        'reset_at' => now_iso(),
    ];
}
