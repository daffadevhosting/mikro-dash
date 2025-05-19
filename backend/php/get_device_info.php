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
    $sysResource = new RouterOS\Request('/system/resource/print');
    $responses = $client->sendSync($sysResource);

    if (count($responses) === 0) {
        throw new Exception('No response from device.');
    }

    $resource = $responses[0];

    $info = [
        'model' => $resource->getProperty('board-name'),
        'uptime' => $resource->getProperty('uptime'),
        'cpu_load' => $resource->getProperty('cpu-load'),
        'ram_usage' => $resource->getProperty('free-memory'),
        'total_ram' => $resource->getProperty('total-memory'),
        'version' => $resource->getProperty('version'),
        'architecture' => $resource->getProperty('architecture-name'),
        'ip' => '192.168.88.1',
        'interface' => 'ether4-ISP'
    ];

    header('Content-Type: application/json');
    echo json_encode($info);
} catch (Exception $e) {
    echo json_encode(['error' => 'Device not connected']);
}
