<?php
/**
 * ADSWIBE® — CSRF Token Generator
 * Called via AJAX on page load to get a fresh CSRF token
 */
@session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode(['token' => $_SESSION['csrf_token']]);
?>
