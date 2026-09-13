<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../api/connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$name = trim((string) ($input['name'] ?? ''));
$rateDownload = trim((string) ($input['rate_download'] ?? ''));
$rateUpload = trim((string) ($input['rate_upload'] ?? ''));

if ($name === '' || $rateDownload === '' || $rateUpload === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nama dan rate bandwidth wajib diisi.']);
    exit;
}

try {
    $data = [
        'name' => $name,
        'rate_download' => $rateDownload,
        'rate_upload' => $rateUpload,
        'rate_limit' => $rateDownload . '/' . $rateUpload,
        'created_at' => date('c'),
    ];
    $id = preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
    $database->getReference('bandwidths/' . $id)->set($data);

    echo json_encode([
        'success' => true,
        'data' => $data,
    ]);
} catch (Throwable $exception) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
