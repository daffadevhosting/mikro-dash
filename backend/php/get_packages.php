<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../api/connect.php';

use PEAR2\Net\RouterOS;

try {
    $responses = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/profile/print'));
    $metadata = $database->getReference('package_metadata')->getValue() ?: [];
    $packages = [];

    foreach ($responses as $response) {
        $profileName = $response->getProperty('name');
        if ($profileName === null || $profileName === '') {
            continue;
        }
        $comment = (string) ($response->getProperty('comment') ?? '');
        $package = $metadata[$profileName] ?? (strpos($comment, 'mikrodash:package:') === 0
            ? json_decode(substr($comment, strlen('mikrodash:package:')), true)
            : []);
        $packages[] = is_array($package) ? $package : [];
        $packages[array_key_last($packages)]['id'] = $profileName;
        $packages[array_key_last($packages)]['nama'] = $packages[array_key_last($packages)]['nama'] ?? $profileName;
        $packages[array_key_last($packages)]['harga'] = $packages[array_key_last($packages)]['harga'] ?? 0;
        $packages[array_key_last($packages)]['price'] = $packages[array_key_last($packages)]['price'] ?? (int) $packages[array_key_last($packages)]['harga'];
        $packages[array_key_last($packages)]['jenis'] = $packages[array_key_last($packages)]['jenis'] ?? 'profile';
        $packages[array_key_last($packages)]['rate_limit'] = $response->getProperty('rate-limit') ?? '';
        $packages[array_key_last($packages)]['session_timeout'] = $packages[array_key_last($packages)]['session_timeout'] ?? ($response->getProperty('session-timeout') ?? '');
        $packages[array_key_last($packages)]['shared_users'] = $packages[array_key_last($packages)]['shared_users'] ?? ($response->getProperty('shared-users') ?? '');
    }

    echo json_encode(['data' => $packages]);
} catch (Throwable $exception) {
    http_response_code(502);
    echo json_encode(['error' => true, 'message' => $exception->getMessage()]);
}
