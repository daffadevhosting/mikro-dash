<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
error_reporting(E_ERROR | E_PARSE);

// Load Firebase dan Mikrotik
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../PEAR2/Autoload.php';
require_once __DIR__ . '/../api/connect.php';
use PEAR2\Net\RouterOS;

// Ambil data dari POST
$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$paketId = $data['paket_id'] ?? '';
$metode = $data['metode'] ?? 'manual';

if (!$username || !$paketId) {
  echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
  exit;
}

// Validasi bahwa profile yang dipilih memang tersedia di MikroTik.
$paket = ['nama' => $paketId, 'harga' => 0, 'price' => 0, 'time_limit' => '', 'quota_limit' => ''];
$profileFound = false;
$profiles = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/profile/print'));
foreach ($profiles as $profile) {
  if ($profile->getProperty('name') !== $paketId) {
    continue;
  }
  $profileFound = true;
  $firebaseMetadata = $database->getReference('package_metadata/' . $paketId)->getValue();
  if (is_array($firebaseMetadata)) {
    $paket = array_merge($paket, $firebaseMetadata, ['nama' => $paketId]);
  }
  $comment = (string) ($profile->getProperty('comment') ?? '');
  $prefix = 'mikrodash:package:';
  if (strpos($comment, $prefix) === 0) {
    $metadata = json_decode(substr($comment, strlen($prefix)), true);
    if (is_array($metadata)) {
      $paket = array_merge($paket, $metadata, ['nama' => $paketId]);
    }
  }
  break;
}

if (!$profileFound) {
  echo json_encode(['success' => false, 'message' => 'Paket tidak ditemukan di MikroTik']);
  exit;
}

// Siapkan parameter user
$setRequest = new RouterOS\Request("/ip/hotspot/user/set");
$id = null;

// Cari berdasarkan nama agar user disabled/expired tetap dapat di-topup.
$findRequest = new RouterOS\Request('/ip/hotspot/user/print');
foreach ($client->sendSync($findRequest) as $response) {
  $responseName = (string) ($response->getProperty('name') ?? '');
  if ($responseName !== '' && strcasecmp($responseName, $username) === 0) {
    $id = $response->getProperty('.id') ?: $response->getArgument('.id');
    break;
  }
}

if (!$id) {
  echo json_encode(['success' => false, 'message' => 'User tidak ditemukan di Mikrotik']);
  exit;
}

// Hitung masa aktif
$masaAktif = $paket['masa_aktif'] ?? 1;
$timeLimit = $paket['time_limit'] ?? '';
$quotaLimit = $paket['quota_limit'] ?? '';
$comment = date('Y-m-d H:i') . " (Topup)";

// Update user
$setRequest->setArgument('.id', $id);
$setRequest->setArgument('profile', $paket['nama']);
$setRequest->setArgument('comment', $comment);
$setRequest->setArgument('disabled', 'no');

if ($timeLimit) $setRequest->setArgument('limit-uptime', $timeLimit);
if ($quotaLimit) $setRequest->setArgument('limit-bytes-total', $quotaLimit);

try {
  $client->sendSync($setRequest);
  $resetRequest = new RouterOS\Request('/ip/hotspot/user/reset-counters');
  $resetRequest->setArgument('.id', $id);
  $client->sendSync($resetRequest);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Gagal set user: ' . $e->getMessage()]);
  exit;
}

// Simpan log transaksi ke Firebase
$logData = [
  'username' => $username,
  'paket_id' => $paketId,
  'nama_paket' => $paket['nama'],
  'harga' => (int) ($paket['price'] ?? $paket['harga'] ?? 0),
  'price' => (int) ($paket['price'] ?? $paket['harga'] ?? 0),
  'waktu' => date('Y-m-d H:i:s'),
  'metode' => $metode
];
$database->getReference('transaksi_topup')->push($logData);

echo json_encode(['success' => true]);
