<?php
/**
 * Mulago Hospital Admin — Save Health Alert (Admin API)
 * Uses Database Abstraction Layer for normalized inserts
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

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['message'] ?? '');
    $severity = trim($_POST['severity'] ?? 'info');

    if (!$title || !$content) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Title and message are required.']);
        exit;
    }

    // Map alert type to severity if provided
    $alertType = trim($_POST['alert_type'] ?? '');
    if ($alertType) {
        $severityMap = [
            'urgent' => 'urgent',
            'campaign' => 'campaign',
            'info' => 'info',
            'maintenance' => 'maintenance'
        ];
        $severity = $severityMap[$alertType] ?? 'info';
    }

    // Create alert via DAL
    $alertId = $db->createAlert($title, $content, $severity);

    echo json_encode([
        'success' => true,
        'id' => $alertId,
        'message' => 'Health alert published successfully.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}