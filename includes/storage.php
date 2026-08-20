<?php
declare(strict_types=1);

function data_path(string $file): string
{
    return DATA_DIR . DIRECTORY_SEPARATOR . $file;
}

function json_read(string $file): array
{
    $path = data_path($file);
    if (!is_file($path)) {
        return [];
    }

    $fp = fopen($path, 'rb');
    if ($fp === false) {
        return [];
    }

    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function json_write(string $file, array $data): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }

    $path = data_path($file);
    $fp = fopen($path, 'c+b');
    if ($fp === false) {
        throw new RuntimeException('Unable to write ' . $file);
    }

    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function append_log(string $event, array $context = []): void
{
    $logs = json_read('logs.json');
    $logs[] = [
        'id' => count($logs) + 1,
        'event' => $event,
        'context' => $context,
        'created_at' => now_iso(),
    ];
    json_write('logs.json', $logs);
}

function find_by_id(array $items, int|string $id): ?array
{
    $id = (int) $id;
    foreach ($items as $item) {
        if ((int) ($item['id'] ?? 0) === $id) {
            return $item;
        }
    }
    return null;
}

function replace_by_id(array $items, array $updated): array
{
    foreach ($items as $i => $item) {
        if ((int) ($item['id'] ?? 0) === (int) $updated['id']) {
            $items[$i] = $updated;
            return $items;
        }
    }
    $items[] = $updated;
    return $items;
}
