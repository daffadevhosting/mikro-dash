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

try {
    $iface = 'ether1-ISP'; // Sesuaikan dengan interfacemu

    $request = new RouterOS\Request('/interface/monitor-traffic', RouterOS\Query::where('interface', $iface));
    $request->setArgument('once', '');
    $response = $client->sendSync($request)->getAllOfType(RouterOS\Response::TYPE_DATA);

    if (count($response) > 0) {
        $stats = $response[0];
        echo json_encode([
            'rx' => intval($stats->getProperty('rx-bits-per-second')),
            'tx' => intval($stats->getProperty('tx-bits-per-second')),
        ]);
    } else {
        echo json_encode(['rx' => 0, 'tx' => 0]);
    }
} catch (Exception $e) {
    echo json_encode(['rx' => 0, 'tx' => 0]);
}
