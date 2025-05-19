<?php
// Header CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 1);

// Load dependencies
require_once __DIR__ . '/../api/connect.php';
$autoloadPath = realpath(__DIR__ . '/../vendor/autoload.php');
if (!$autoloadPath) {
    die("Autoload not found at expected path.");
}
require_once $autoloadPath;
require_once __DIR__ . '/../firebase/firebase.php';

// Get input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Save to Firebase
$firebase = new FirebaseDB();
$db = $firebase->getDB();

$routerId = uniqid('router_');
$db->getReference('routers/' . $routerId)->set($data);

echo json_encode(['success' => true]);
