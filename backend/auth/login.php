<?php
putenv('CURL_CA_BUNDLE=' . realpath('D:/hotspot-project/cacert.pem'));
error_reporting(E_ALL & ~E_DEPRECATED);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require __DIR__ . '/../vendor/autoload.php'; // Autoload dulu!

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;

// Fungsi encode email agar jadi key aman
function encodeEmailKey($email) {
    return str_replace('.', '_', $email);
}

$serviceAccountPath = realpath(__DIR__ . '/secret/adminsdk.json');
if (!$serviceAccountPath) {
    die("Service account JSON tidak ditemukan!");
}
$firebase = (new Factory)->withServiceAccount($serviceAccountPath);

$database = $firebase->createDatabase();

// Ambil input
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo "Username dan password wajib diisi!";
    exit;
}

try {
    $safeUsername = encodeEmailKey($username);
    $adminRef = $database->getReference("admins/{$safeUsername}");
    $adminData = $adminRef->getValue();

    if (!$adminData) {
        echo "Username tidak ditemukan.";
        exit;
    }

    if (!password_verify($password, $adminData['password'])) {
        echo "Password salah.";
        exit;
    }

    $_SESSION['admin'] = $username;

    // Redirect ke dashboard
    header('Location: /dashboard');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Terjadi kesalahan: " . $e->getMessage();
    exit;
}
