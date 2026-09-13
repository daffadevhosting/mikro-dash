<?php
include __DIR__ . '/../auth/auth.php';

// Kalau sudah login, tampilkan dashboard HTML
header('Location: http://localhost:1111/dashboard');
exit;
