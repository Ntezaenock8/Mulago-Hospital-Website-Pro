<?php
/**
 * Mulago Hospital — Get Clinic Hours API
 * Returns clinic hours for all departments or a specific department
 */

header('Content-Type: application/json');

require __DIR__ . '/database.php';

try {
  $db = new MulagoDatabase();
  $deptCode = $_GET['dept'] ?? null;

  if ($deptCode) {
    // Get clinic hours for a specific department
    $hours = $db->getClinicHoursByDepartmentCode($deptCode);
    if (!$hours) {
      echo json_encode(['success' => false, 'error' => 'Department not found']);
      exit;
    }
    echo json_encode(['success' => true, 'data' => $hours]);
  } else {
    // Get all clinic hours
    $hours = $db->getClinicHours();
    echo json_encode(['success' => true, 'data' => $hours]);
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
