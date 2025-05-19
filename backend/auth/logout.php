<?php
// Mulai session
session_start();

// Hapus semua sesi yang ada
session_unset();

// Hancurkan session
session_destroy();

// Redirect ke halaman login
header("Location: http://localhost:1111/login");
exit;
