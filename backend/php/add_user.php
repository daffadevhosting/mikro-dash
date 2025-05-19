<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/PEAR2/Autoload.php';

use PEAR2\Net\RouterOS;
use Kreait\Firebase\Factory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$firebaseUri = getenv('FIREBASE_DB');

// Inisialisasi Firebase
$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/../firebase/firebase-adminsdk.json')
    ->withDatabaseUri($firebaseUri);
$database = $factory->createDatabase();

// Ambil config router dari Firebase
$snapshot = $database->getReference('routers/default')->getSnapshot();
$config = $snapshot->getValue();

if (!$config) {
    echo json_encode(['success' => false, 'message' => 'Router config not found']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['username'], $data['password'], $data['profile'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $client = new RouterOS\Client(
        $config['ip'],
        $config['username'],
        $config['password']
    );

    // Tambah user ke Mikrotik
    $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
    $addRequest->setArgument('name', $data['username']);
    $addRequest->setArgument('password', $data['password']);
    $addRequest->setArgument('profile', $data['profile']);
    $client->sendSync($addRequest);

    // Simpan ke Firebase (users/hotspot)
    $database->getReference('custemers/hotspot/' . $data['username'])->set([
        'username' => $data['username'],
        'password' => $data['password'],
        'profile' => $data['profile'],
        'created_at' => date('c')
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
