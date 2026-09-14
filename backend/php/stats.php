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
require_once __DIR__ . '/../PEAR2/Autoload.php';
use PEAR2\Net\RouterOS;

header('Content-Type: application/json');

try {
    $users = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/print'));
    $activeUsers = $client->sendSync(new RouterOS\Request('/ip/hotspot/active/print'));
    $visibleUserCount = 0;
    $visibleOnlineCount = 0;

    foreach ($users as $user) {
        $username = trim((string) $user->getProperty('name'));
        if ($user->getType() === RouterOS\Response::TYPE_DATA
            && $username !== ''
            && strcasecmp($username, 'default-trial') !== 0) {
            $visibleUserCount++;
        }
    }
    foreach ($activeUsers as $activeUser) {
        $username = trim((string) $activeUser->getProperty('user'));
        if ($activeUser->getType() === RouterOS\Response::TYPE_DATA
            && $username !== ''
            && strcasecmp($username, 'default-trial') !== 0) {
            $visibleOnlineCount++;
        }
    }

    echo json_encode([
        'totalUsers' => $visibleUserCount,
        'onlineUsers' => $visibleOnlineCount
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
