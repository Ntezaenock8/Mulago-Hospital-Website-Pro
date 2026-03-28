<?php
/**
 * Mulago Hospital Admin — Update Health Alert
 * Supports: Edit content, Toggle active/inactive status
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
    $action = trim($_POST['action'] ?? '');

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

    if ($action === 'toggle') {
        // Toggle active/inactive status
        $db->toggleAlertStatus($alertId);
        $updated = $db->getAlert($alertId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Alert status toggled successfully.',
            'is_active' => $updated['is_active']
        ]);

    } elseif ($action === 'edit') {
        // Edit alert content
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['message'] ?? '');
        $severity = trim($_POST['alert_type'] ?? $existing['severity']);

        if (!$title || !$content) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Title and message are required.']);
            exit;
        }

        // Map alert type to severity
        $severityMap = [
            'urgent' => 'urgent',
            'campaign' => 'campaign',
            'info' => 'info',
            'maintenance' => 'maintenance'
        ];
        $severity = $severityMap[$severity] ?? 'info';

        $db->updateAlert($alertId, $title, $content, $severity);
        
        echo json_encode([
            'success' => true,
            'message' => 'Alert updated successfully.',
            'id' => $alertId
        ]);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action. Use "toggle" or "edit".']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
