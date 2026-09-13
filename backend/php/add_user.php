<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../PEAR2/Autoload.php';
require_once __DIR__ . '/../api/connect.php';

use PEAR2\Net\RouterOS;

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['username'], $data['password'], $data['profile'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $profiles = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/profile/print'));
    $profileExists = false;
    foreach ($profiles as $profile) {
        if (strcasecmp((string) ($profile->getProperty('name') ?? ''), $data['profile']) === 0) {
            $profileExists = true;
            break;
        }
    }
    if (!$profileExists) {
        throw new RuntimeException("Profile MikroTik '{$data['profile']}' tidak ditemukan.");
    }

    $users = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/print'));
    foreach ($users as $user) {
        if (strcasecmp((string) ($user->getProperty('name') ?? ''), $data['username']) === 0) {
            throw new RuntimeException("User MikroTik '{$data['username']}' sudah ada.");
        }
    }

    // Tambah user ke Mikrotik
    $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
    $addRequest->setArgument('name', $data['username']);
    $addRequest->setArgument('password', $data['password']);
    $addRequest->setArgument('profile', $data['profile']);
    $responses = $client->sendSync($addRequest);
    foreach ($responses as $response) {
        if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
            throw new RuntimeException($response->getProperty('message') ?: 'MikroTik menolak pembuatan user.');
        }
    }

    $verifyUsers = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/print'));
    $created = false;
    foreach ($verifyUsers as $user) {
        if (strcasecmp((string) ($user->getProperty('name') ?? ''), $data['username']) === 0) {
            $created = true;
            break;
        }
    }
    if (!$created) {
        throw new RuntimeException('User tidak ditemukan setelah dibuat di MikroTik.');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
