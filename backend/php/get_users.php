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

try {
    $request = new RouterOS\Request('/ip/hotspot/user/print');
    $responses = $client->sendSync($request);

    foreach ($responses as $res) {
        $username = (string) $res->getProperty('name');
        if (strcasecmp($username, 'default-trial') === 0) {
            continue;
        }
        echo "User: " . $username . "<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>