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
require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$firebaseUri = getenv('FIREBASE_DB');

class FirebaseDB {
    private $database;

    public function __construct() {
        $serviceAccountPath = __DIR__ . '/firebase-adminsdk.json';  // Harus Ada

        $factory = (new Factory)
            ->withServiceAccount($serviceAccountPath)
            ->withDatabaseUri($firebaseUri);

        $this->database = $factory->createDatabase();
    }

    public function getDB() {
        return $this->database;
    }
}
