<?php
// Header CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Tampilkan hanya error fatal, bukan deprecated
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 1);

// Include library
require_once __DIR__ . '/../api/connect.php';
require_once __DIR__ . '/../vendor/PEAR2/Autoload.php';
use PEAR2\Net\RouterOS;

header('Content-Type: application/json');

try {
    $users = [];
    $userList = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/print'));
    $activeList = $client->sendSync(new RouterOS\Request('/ip/hotspot/active/print'));

    $activeMap = [];
    foreach ($activeList as $active) {
        $activeMap[$active->getProperty('user')] = [
            'address' => $active->getProperty('address') ?? '-',
            'mac' => $active->getProperty('mac-address') ?? '-',
            'uptime' => $active->getProperty('uptime') ?? '-',
        ];
    }

    $onlineUsers = [];
    $offlineUsers = [];

    foreach ($userList as $user) {
        $username = $user->getProperty('name');
        $profile = $user->getProperty('profile');
        $ip = '-';
        $mac = '-';
        $uptime = 'Expired';
        $status = 'offline';

        if (isset($activeMap[$username])) {
            $status = 'online';
            $ip = $activeMap[$username]['address'];
            $mac = $activeMap[$username]['mac'];
            $uptime = $activeMap[$username]['uptime'];
        }

        $data = [
            'username' => $username,
            'address' => $ip,
            'mac' => $mac,
            'profile' => $profile,
            'uptime' => $uptime,
            'status' => $status
        ];

        if ($status === 'online') {
            $onlineUsers[] = $data;
        } else {
            $offlineUsers[] = $data;
        }
    }

    echo json_encode(['data' => array_merge($onlineUsers, $offlineUsers)]);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
