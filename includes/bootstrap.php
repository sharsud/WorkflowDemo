<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('ROOT_DIR', dirname(__DIR__));
define('DATA_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'data');
define('INCLUDES_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'includes');

require_once INCLUDES_DIR . DIRECTORY_SEPARATOR . 'helpers.php';
require_once INCLUDES_DIR . DIRECTORY_SEPARATOR . 'storage.php';
require_once INCLUDES_DIR . DIRECTORY_SEPARATOR . 'seed.php';
require_once INCLUDES_DIR . DIRECTORY_SEPARATOR . 'auth.php';

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

ensure_seed_data();
