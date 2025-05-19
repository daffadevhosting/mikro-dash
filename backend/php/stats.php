<?php
// Izinkan CORS dari semua origin (bisa kamu ganti jadi spesifik origin jika perlu)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../api/connect.php';
require_once __DIR__ . '/../vendor/PEAR2/Autoload.php';
use PEAR2\Net\RouterOS;

header('Content-Type: application/json');

try {
    $total = count($client->sendSync(new RouterOS\Request('/ip/hotspot/user/print')));
    $online = count($client->sendSync(new RouterOS\Request('/ip/hotspot/active/print')));

    echo json_encode([
        'totalUsers' => $total,
        'onlineUsers' => $online
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
