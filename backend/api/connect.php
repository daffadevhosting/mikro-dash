<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../PEAR2/Autoload.php';

use PEAR2\Net\RouterOS;
use Kreait\Firebase\Factory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$firebaseUri = $_ENV['FIREBASE_DB'] ?? $_SERVER['FIREBASE_DB'] ?? getenv('FIREBASE_DB');
if (!$firebaseUri) {
    http_response_code(500);
    die('FIREBASE_DB is not configured.');
}
$serviceAccountPath = __DIR__ . '/../auth/secret/firebase-adminsdk.json';

// Inisialisasi Firebase
$factory = (new Factory)
    ->withServiceAccount($serviceAccountPath)
    ->withDatabaseUri($firebaseUri);

$database = $factory->createDatabase(); // Tambahkan baris ini!

// Ambil data router dari Firebase
$routerSnapshot = $database->getReference('routers/default')->getSnapshot();
$router = $routerSnapshot->getValue();

if (!$router || !isset($router['ip'], $router['username'], $router['password'])) {
    $router = [
        'ip' => $_ENV['ROUTER_IP'] ?? $_SERVER['ROUTER_IP'] ?? getenv('ROUTER_IP'),
        'username' => $_ENV['ROUTER_USER'] ?? $_SERVER['ROUTER_USER'] ?? getenv('ROUTER_USER'),
        'password' => $_ENV['ROUTER_PASS'] ?? $_SERVER['ROUTER_PASS'] ?? getenv('ROUTER_PASS'),
    ];
}

if (!$router['ip'] || !$router['username'] || !$router['password']) {
    http_response_code(500);
    die('Router configuration is missing from Firebase and backend/.env.');
}

// Buat koneksi Mikrotik
try {
    $client = new RouterOS\Client(
        $router['ip'],
        $router['username'],
        $router['password']
    );
} catch (Exception $e) {
    die("Router connection failed: " . $e->getMessage());
}
?>
