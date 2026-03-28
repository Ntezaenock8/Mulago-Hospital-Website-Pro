<?php
/**
 * Mulago Hospital Admin — Update Appointment Status (Admin API)
 * Uses Database Abstraction Layer for normalized updates
 */

header('Content-Type: application/json');
session_start();

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/Database.php';

try {
    $db = new MulagoDatabase();

    $appointmentId = (int)($_POST['id'] ?? 0);
    $statusCode = trim($_POST['status'] ?? '');

    if (!$appointmentId || !$statusCode) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing appointment ID or status.']);
        exit;
    }

    // Update status via DAL
    $db->updateAppointmentStatus($appointmentId, $statusCode);

    echo json_encode([
        'success' => true,
        'message' => "Appointment status updated to '$statusCode'."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}