<?php
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

$database = $factory->createDatabase(); // Tambahkan baris ini!

// Ambil data router dari Firebase
$routerSnapshot = $database->getReference('routers/default')->getSnapshot();
$router = $routerSnapshot->getValue();

if (!$router || !isset($router['ip'], $router['username'], $router['password'])) {
    die("Router config not found in Firebase.");
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
