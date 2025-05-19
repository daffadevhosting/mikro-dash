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
    $request = new RouterOS\Request('/log/print');
    $responses = $client->sendSync($request);

    $logs = [];

    foreach ($responses as $response) {
        if ($response instanceof RouterOS\Response) {
            $logs[] = [
                'time' => $response->getProperty('time') ?? '',
                'message' => $response->getProperty('message') ?? '',
                'topic' => $response->getProperty('topics') ?? 'unknown'
            ];
        }
    }

    echo json_encode(['logs' => $logs]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Gagal ambil log dari perangkat']);
}
