<?php
/**
 * Mulago Hospital — Save Clinic Hours API (Admin Only)
 * Updates clinic hours for a department
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
    $data = $_POST;
  }

  $deptCode = isset($data['department']) ? $data['department'] : null;
  $monday = isset($data['monday']) ? (int)$data['monday'] : 0;
  $tuesday = isset($data['tuesday']) ? (int)$data['tuesday'] : 0;
  $wednesday = isset($data['wednesday']) ? (int)$data['wednesday'] : 0;
  $thursday = isset($data['thursday']) ? (int)$data['thursday'] : 0;
  $friday = isset($data['friday']) ? (int)$data['friday'] : 0;
  $saturday = isset($data['saturday']) ? (int)$data['saturday'] : 0;
  $sunday = isset($data['sunday']) ? (int)$data['sunday'] : 0;
  $openTime = isset($data['open_time']) ? $data['open_time'] : '08:00';
  $closeTime = isset($data['close_time']) ? $data['close_time'] : '16:00';
  $fee = isset($data['fee']) ? $data['fee'] : '10,000';
  $notes = isset($data['notes']) ? $data['notes'] : '';

  if (!$deptCode) {
    echo json_encode(['success' => false, 'error' => 'Department code required']);
    exit;
  }

  $db = new MulagoDatabase();
  $db->updateClinicHours($deptCode, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $openTime, $closeTime, $fee, $notes);

  echo json_encode(['success' => true, 'message' => 'Clinic hours updated successfully']);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
