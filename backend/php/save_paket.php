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

try {
  $input = json_decode(file_get_contents("php://input"), true);

  $stmt = $pdo->prepare("INSERT INTO paket_hotspot 
    (nama, jenis, bandwidth_id, harga, masa_aktif, time_limit, quota_limit, shared_users, session_timeout, script) 
    VALUES (:nama, :jenis, :bandwidth_id, :harga, :masa_aktif, :time_limit, :quota_limit, :shared_users, :session_timeout, :script)");

  $stmt->execute([
    ':nama' => $input['nama'],
    ':jenis' => $input['jenis'],
    ':bandwidth_id' => $input['bandwidth_id'],
    ':harga' => $input['harga'],
    ':masa_aktif' => $input['masa_aktif'],
    ':time_limit' => $input['time_limit'] ?? null,
    ':quota_limit' => $input['quota_limit'] ?? null,
    ':shared_users' => $input['shared_users'] ?? 1,
    ':session_timeout' => $input['session_timeout'] ?? null,
    ':script' => $input['script']
  ]);

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>