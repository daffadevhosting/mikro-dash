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
    $values = $database->getReference('transaksi_topup')->getValue() ?: [];
    $today = date('Y-m-d');
    $month = date('Y-m');
    $incomeToday = 0;
    $incomeMonth = 0;

    foreach ($values as $transaction) {
        if (!is_array($transaction)) {
            continue;
        }
        $amount = (float) ($transaction['harga'] ?? 0);
        $time = (string) ($transaction['waktu'] ?? '');
        if (strpos($time, $today) === 0) {
            $incomeToday += $amount;
        }
        if (strpos($time, $month) === 0) {
            $incomeMonth += $amount;
        }
    }

    echo json_encode([
        'today' => $incomeToday,
        'month' => $incomeMonth,
    ]);
} catch (Throwable $exception) {
    http_response_code(502);
    echo json_encode(['error' => true, 'message' => $exception->getMessage()]);
}
