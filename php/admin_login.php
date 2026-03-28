<?php
/**
 * Mulago Hospital Admin — Login Handler
 * In production: use password_hash/password_verify with DB stored hashes
 * For this demo: hardcoded credentials (NEVER do this in production)
 */

header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

// Handle both JSON and form-encoded POST data
$username = '';
$password = '';

if (isset($_POST['username'])) {
    // Form-encoded data
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
} else {
    // JSON data
    $json = json_decode(file_get_contents('php://input'), true);
    $username = trim($json['username'] ?? '');
    $password = trim($json['password'] ?? '');
}

// ─────────────────────────────────────────────────────────────
// DEMO CREDENTIALS — Replace with DB lookup + password_verify()
// in a real deployment. Never store plaintext passwords.
// ─────────────────────────────────────────────────────────────
$ADMIN_USERS = [
    'admin'   => 'mulago2024',
    'matron'  => 'mulago_matron',
    'records' => 'records_desk',
];

if (!array_key_exists($username, $ADMIN_USERS)) {
    echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
    exit;
}

if ($ADMIN_USERS[$username] !== $password) {
    echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
    exit;
}

// Set session
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user']      = $username;
$_SESSION['login_time']      = time();

echo json_encode([
    'success'  => true,
    'username' => $username,
    'message'  => 'Login successful.'
]);
