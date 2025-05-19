<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
error_reporting(E_ERROR | E_PARSE);

// Load Firebase dan Mikrotik
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/PEAR2/Autoload.php';
use PEAR2\Net\RouterOS;
use Kreait\Firebase\Factory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$firebaseUri = getenv('FIREBASE_DB');

// Init Firebase
$firebase = (new Factory)
  ->withServiceAccount(__DIR__ . '/../firebase/firebase-adminsdk.json')
  ->withDatabaseUri($firebaseUri);

$db = $firebase->createDatabase();

// Ambil data dari POST
$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$paketId = $data['paket_id'] ?? '';
$metode = $data['metode'] ?? 'manual';

if (!$username || !$paketId) {
  echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
  exit;
}

// Ambil router & paket
$router = $db->getReference('routers/default')->getValue();
$paket = $db->getReference('pakets/' . $paketId)->getValue();

if (!$router || !$paket) {
  echo json_encode(['success' => false, 'message' => 'Router/Paket tidak ditemukan']);
  exit;
}

// Koneksi Mikrotik
try {
  $client = new RouterOS\Client($router['ip'], $router['username'], $router['password']);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Gagal konek ke Router: ' . $e->getMessage()]);
  exit;
}

// Siapkan parameter user
$setRequest = new RouterOS\Request("/ip/hotspot/user/set");
$findRequest = new RouterOS\Request("/ip/hotspot/user/print .proplist=.id");
$findRequest->setArgument('?.name', $username);

$id = $client->sendSync($findRequest)->getArgument('.id');

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

if ($timeLimit) $setRequest->setArgument('limit-uptime', $timeLimit);
if ($quotaLimit) $setRequest->setArgument('limit-bytes-total', $quotaLimit);

try {
  $client->sendSync($setRequest);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Gagal set user: ' . $e->getMessage()]);
  exit;
}

// Simpan log transaksi ke Firebase
$logData = [
  'username' => $username,
  'paket_id' => $paketId,
  'nama_paket' => $paket['nama'],
  'harga' => $paket['harga'],
  'waktu' => date('Y-m-d H:i:s'),
  'metode' => $metode
];
$db->getReference('transaksi_topup')->push($logData);

echo json_encode(['success' => true]);
