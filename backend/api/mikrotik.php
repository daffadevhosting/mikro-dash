<?php
// Izinkan CORS dari semua origin (bisa kamu ganti jadi spesifik origin jika perlu)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../api/connect.php';
require_once __DIR__ . '/../vendor/PEAR2/Autoload.php';

use PEAR2\Net\RouterOS;

$status = 'default';
$message = '';

try {
    $status = 'success';
    $message = '<i class="bi bi-check2-all"></i> Koneksi ke Router Berhasil!';
} catch (Exception $e) {
    $status = 'danger';
    $message = '<i class="bi bi-x-octagon-fill"></i> Gagal konek: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Test Koneksi Mikrotik</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #1f1c2c, #928dab);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }
    .card {
      border-radius: 1rem;
      box-shadow: 0 0 20px rgba(0,0,0,0.3);
      background: #fff;
      padding: 2rem;
      max-width: 500px;
    }
    .status-icon {
      font-size: 3rem;
    }
  </style>
</head>
<body>

  <div class="card text-center">
    <h2 class="mb-3"><i class="bi bi-router-fill"></i> Tes Koneksi Mikrotik</h2>
    <div class="alert alert-<?php echo $status; ?>" role="alert">
      <div class="status-icon">
        <?php echo $status === 'success' ? '<i class="bi bi-check2-circle"></i>' : '<i class="bi bi-x-octagon"></i>'; ?>
      </div>
      <p class="mt-2"><?php echo $message; ?></p>
    </div>
    <a href="../dashboard" class="btn btn-outline-secondary mt-3"><i class="bi bi-backspace"></i> Kembali ke Dashboard</a>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
