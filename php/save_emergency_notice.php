<?php
/**
 * Mulago Hospital — Save Emergency Notice API (Admin Only)
 * Updates the emergency notice
 */

header('Content-Type: application/json');
session_start();

// Check admin auth
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
  exit;
}

require __DIR__ . '/database.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
  }

  $inputData = file_get_contents('php://input');
  $data = json_decode($inputData, true);
  
  if (!$data) {
    // Try POST parameters if JSON fails
    $data = $_POST;
  }

  $content = isset($data['content']) ? $data['content'] : '';
  $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 0;

  $db = new MulagoDatabase();
  $db->saveEmergencyNotice($content, $isActive);

  echo json_encode(['success' => true, 'message' => 'Emergency notice saved successfully']);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
