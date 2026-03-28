<?php
/**
 * Mulago Hospital — Fetch Health Alerts
 * Public API (no auth required for frontend)
 * Admin: Gets all alerts | Public: Gets only active alerts
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/Database.php';

try {
    $db = new MulagoDatabase();

    // Check if this is an admin request (has session)
    $isAdmin = isset($_SESSION['admin_logged_in']);
    
    // GET parameter to specify which set to fetch
    $scope = trim($_GET['scope'] ?? 'public');
    
    if ($scope === 'admin' && !$isAdmin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    if ($scope === 'admin' && $isAdmin) {
        // Admin gets all alerts (active and inactive)
        $alerts = $db->getAllAlerts();
        $message = 'All alerts retrieved.';
    } else {
        // Public gets only active alerts
        $alerts = $db->getActiveAlerts();
        $message = 'Active alerts retrieved.';
    }

    echo json_encode([
        'success' => true,
        'data' => $alerts,
        'total' => count($alerts),
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
