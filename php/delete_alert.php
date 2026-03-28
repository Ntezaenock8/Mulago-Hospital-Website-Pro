<?php
/**
 * Mulago Hospital Admin — Delete Health Alert
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

    $alertId = (int)($_POST['alert_id'] ?? 0);

    if (!$alertId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Alert ID is required.']);
        exit;
    }

    // Verify alert exists
    $existing = $db->getAlert($alertId);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Alert not found.']);
        exit;
    }

    // Delete alert
    $db->deleteAlert($alertId);

    echo json_encode([
        'success' => true,
        'message' => 'Alert deleted successfully.',
        'id' => $alertId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
