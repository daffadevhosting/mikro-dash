<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../api/connect.php';

use PEAR2\Net\RouterOS;

$input = json_decode(file_get_contents('php://input'), true);
$packageName = trim((string) ($input['nama'] ?? ''));
$bandwidthName = trim((string) ($input['bandwidth_id'] ?? ''));
$sessionInput = trim((string) ($input['session_timeout'] ?? '01:00:00'));

if ($packageName === '' || $bandwidthName === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nama paket dan bandwidth wajib diisi.']);
    exit;
}

if (preg_match('/^(\d+)h$/i', $sessionInput, $match)) {
    $sessionInput = sprintf('%02d:00:00', (int) $match[1]);
} elseif (preg_match('/^(\d+)m$/i', $sessionInput, $match)) {
    $sessionInput = sprintf('00:%02d:00', (int) $match[1]);
} elseif (preg_match('/^(\d+)s$/i', $sessionInput, $match)) {
    $sessionInput = sprintf('00:00:%02d', (int) $match[1]);
} elseif (preg_match('/^\d+$/', $sessionInput)) {
    $sessionInput = sprintf('%02d:00:00', (int) $sessionInput);
}

try {
    $profiles = $database->getReference('bandwidths')->getSnapshot()->getValue() ?: [];
    $rateLimit = null;

    foreach ($profiles as $profile) {
        if (is_array($profile) && ($profile['name'] ?? '') === $bandwidthName) {
            $rateLimit = (string) ($profile['rate_limit'] ?? (($profile['rate_download'] ?? '') . '/' . ($profile['rate_upload'] ?? '')));
            break;
        }
    }

    if ($rateLimit === null) {
        throw new RuntimeException('Bandwidth profile tidak ditemukan di Firebase.');
    }

    $package = [
        'nama' => $packageName,
        'jenis' => (string) ($input['jenis'] ?? ''),
        'bandwidth_id' => $bandwidthName,
        'harga' => (string) ($input['harga'] ?? '0'),
        'price' => (int) ($input['harga'] ?? 0),
        'masa_aktif' => (string) ($input['masa_aktif'] ?? '1'),
        'time_limit' => (string) ($input['time_limit'] ?? ''),
        'quota_limit' => (string) ($input['quota_limit'] ?? ''),
        'shared_users' => (string) ($input['shared_users'] ?? '1'),
        'session_timeout' => $sessionInput,
        'script' => (string) ($input['script'] ?? ''),
    ];

    $existingProfiles = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/profile/print'));
    $existingProfileId = null;
    foreach ($existingProfiles as $existingProfile) {
        $existingName = (string) ($existingProfile->getProperty('name') ?: $existingProfile->getArgument('name'));
        if (strcasecmp($existingName, $packageName) === 0) {
            $existingProfileId = $existingProfile->getProperty('.id') ?: $existingProfile->getArgument('.id');
            break;
        }
    }

    $request = new RouterOS\Request($existingProfileId ? '/ip/hotspot/user/profile/set' : '/ip/hotspot/user/profile/add');
    $request->setArgument('name', $packageName);
    if ($existingProfileId) {
        $request->setArgument('.id', $existingProfileId);
    }
    $request->setArgument('rate-limit', $rateLimit);
    if ($package['shared_users'] !== '') {
        $request->setArgument('shared-users', $package['shared_users']);
    }
    if ($package['session_timeout'] !== '') {
        $request->setArgument('session-timeout', $package['session_timeout']);
    }
    if ($package['script'] !== '') {
        $request->setArgument('on-login', $package['script']);
    }
    $responses = $client->sendSync($request);
    foreach ($responses as $response) {
        if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
            $message = $response->getProperty('message') ?: 'MikroTik menolak pembuatan profile.';
            throw new RuntimeException($message);
        }
    }

    $verifyRequest = new RouterOS\Request('/ip/hotspot/user/profile/print');
    $created = false;
    foreach ($client->sendSync($verifyRequest) as $response) {
        $verifiedName = (string) ($response->getProperty('name') ?: $response->getArgument('name'));
        if (strcasecmp($verifiedName, $packageName) === 0) {
            $created = true;
            break;
        }
    }
    if (!$created) {
        throw new RuntimeException('Profile tidak ditemukan setelah dibuat di MikroTik.');
    }

    $database->getReference('package_metadata/' . $packageName)->set($package);

    echo json_encode(['success' => true, 'data' => $package + ['id' => $packageName]]);
} catch (Throwable $exception) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
