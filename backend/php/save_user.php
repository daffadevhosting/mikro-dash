<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}

// Include lib dan koneksi ke Firebase
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/PEAR2/Autoload.php';

use PEAR2\Net\RouterOS;
use Kreait\Firebase\Factory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$firebaseUri = getenv('FIREBASE_DB');

// Init Firebase
$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/../firebase/firebase-adminsdk.json')
    ->withDatabaseUri($firebaseUri);

$database = $factory->createDatabase();

// Ambil konfigurasi router
$routerData = $database->getReference('routers/default')->getSnapshot()->getValue();

if (!$routerData || !isset($routerData['ip'], $routerData['username'], $routerData['password'])) {
    echo json_encode(['success' => false, 'message' => 'Router config not found']);
    exit;
}

// Ambil data dari frontend
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['username'], $input['password'], $input['profile'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Koneksi Mikrotik
    $client = new RouterOS\Client(
        $routerData['ip'],
        $routerData['username'],
        $routerData['password']
    );

    // Tambah user ke Mikrotik
    $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
    $addRequest->setArgument('name', $input['username']);
    $addRequest->setArgument('password', $input['password']);
    $addRequest->setArgument('profile', $input['profile']);

    $client->sendSync($addRequest);

    // Simpan ke Firebase log
    $logPath = 'users_log/' . $input['username'] . '_' . time();
    $database->getReference($logPath)->set([
        'username' => $input['username'],
        'profile' => $input['profile'],
        'time' => date('Y-m-d H:i:s'),
        'ip' => $routerData['ip']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Router error: ' . $e->getMessage()]);
}
