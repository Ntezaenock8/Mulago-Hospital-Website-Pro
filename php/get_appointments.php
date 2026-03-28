<?php
/**
 * Mulago Hospital Admin — Fetch Appointments (Admin API)
 * Uses Database Abstraction Layer for normalized queries
 * Handles filtering, searching, and pagination
 */

header('Content-Type: application/json');
session_start();

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/Database.php';

try {
    $db = new MulagoDatabase();

    // Get filter parameters
    $search = trim($_GET['search'] ?? '');
    $dept = trim($_GET['dept'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $date = trim($_GET['date'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));

    // Fetch appointments via DAL
    $result = $db->getAppointments($page, 8, $search, $dept, $status, $date);
    
    // Get stats (always get global stats, not filtered)
    $stats = $db->getAppointmentStats();

    echo json_encode([
        'success' => true,
        'data' => $result['data'],
        'total' => $result['total'],
        'page' => $result['page'],
        'perPage' => $result['perPage'],
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}