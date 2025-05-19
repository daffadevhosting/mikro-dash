<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Pastikan PEAR2 sudah bisa dipanggil
require_once __DIR__ . '/../vendor/PEAR2/Autoload.php';
use PEAR2\Net\RouterOS;

// Ambil data dari POST JSON
$data = json_decode(file_get_contents('php://input'), true);

// Debug: Tampilkan data yang diterima
file_put_contents("php://stderr", print_r($data, TRUE));

// Validasi sederhana
if (!isset($data['router']) || !isset($data['username']) || !isset($data['password']) || !isset($data['profile'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap.']);
    exit;
}

// Router info dari frontend
$router = $data['router'];
$username = $data['username'];
$password = $data['password'];
$profile = $data['profile'];

try {
    // Split IP dan port (jika pakai tunnel port seperti sg-05.tunnel.web.id:4905)
    $parts = explode(':', $router['ip_address']);
    $host = $parts[0];
    $port = isset($parts[1]) ? (int)$parts[1] : 8728;

    // Koneksi ke Mikrotik
    $client = new RouterOS\Client($host, $router['username'], $router['password'], $port);

    // Kirim perintah untuk buat user Hotspot
    $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
    $addRequest->setArgument('name', $username);
    $addRequest->setArgument('password', $password);
    $addRequest->setArgument('profile', $profile);

    $client->sendSync($addRequest);

    echo json_encode(['success' => true, 'message' => 'Topup berhasil.']);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

// Setelah berhasil topup ke Mikrotik, simpan ke Firebase
$firebaseUrl = "https://whatsgo-id.firebaseio.com/recharges.json"; // Path ke 'recharges'
$rechargeData = [
    'customer_id' => $data['customer_id'],
    'username'    => $username,
    'plan_id'     => $data['plan_id'],
    'namebp'      => $profile,
    'recharged_on'=> date('Y-m-d'),
    'expiration'  => date('Y-m-d', strtotime('+1 days')), // Bisa disesuaikan
    'time'        => date('H:i:s'),
    'status'      => 'active',
    'method'      => $data['method'],
    'routers'     => $router['name'],
    'type'        => $data['type']
];

// Kirim ke Firebase via cURL
$ch = curl_init($firebaseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rechargeData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
?>
