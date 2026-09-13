<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Hanya menerima metode POST.']);
    exit;
}

require_once __DIR__ . '/../api/connect.php';

use PEAR2\Net\RouterOS;

$headers = function_exists('getallheaders') ? getallheaders() : [];
$authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Login diperlukan untuk memakai terminal.']);
    exit;
}

try {
    $factory = (new \Kreait\Firebase\Factory)
        ->withServiceAccount(__DIR__ . '/../auth/secret/firebase-adminsdk.json');
    $factory->createAuth()->verifyIdToken($matches[1]);
} catch (\Throwable $exception) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token Firebase tidak valid atau sudah kedaluwarsa.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$command = trim((string) ($input['command'] ?? $_POST['command'] ?? ''));

if ($command === '' || $command[0] !== '/') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Command RouterOS harus diawali /.']);
    exit;
}

try {
    $responses = $client->sendSync(new RouterOS\Request($command));
    $output = [];
    $hasError = false;

    foreach ($responses as $response) {
        if ($response->getType() === RouterOS\Response::TYPE_DATA) {
            $row = [];
            foreach ($response as $key => $value) {
                $row[$key] = (string) $value;
            }
            $output[] = $row;
        } elseif ($response->getType() === RouterOS\Response::TYPE_ERROR) {
            $hasError = true;
            $output[] = [
                'error' => $response->getProperty('message') ?: $response->getArgument('message') ?: 'RouterOS command failed.',
                'category' => $response->getProperty('category') ?: $response->getArgument('category') ?: '',
            ];
        }
    }

    echo json_encode([
        'success' => !$hasError,
        'command' => $command,
        'data' => $output,
    ]);
} catch (Throwable $exception) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
}
