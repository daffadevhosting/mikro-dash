<?php
session_start();

if (!isset($_SESSION['admin'])) {
    // Redirect ke frontend dashboard di Jekyll
header('Location: http://localhost:1111/dashboard');

    exit;
}
