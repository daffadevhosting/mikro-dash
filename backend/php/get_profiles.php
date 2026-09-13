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

try {
    $profiles = [];
    $snapshot = $database->getReference('bandwidths')->getSnapshot();
    $values = $snapshot->getValue() ?: [];

    foreach ($values as $profile) {
        if (is_array($profile) && !empty($profile['name'])) {
            $profiles[] = $profile;
        }
    }

    echo json_encode(['data' => $profiles]);
} catch (Throwable $exception) {
    http_response_code(502);
    echo json_encode(['error' => true, 'message' => $exception->getMessage()]);
}
