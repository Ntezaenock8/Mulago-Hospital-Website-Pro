<?php
/**
 * Mulago Hospital — Check Department Status API
 * Checks if a department is open on a specific date/time
 */

header('Content-Type: application/json');

require __DIR__ . '/database.php';

try {
  $deptCode = $_GET['dept'] ?? null;
  $date = $_GET['date'] ?? null;
  $time = $_GET['time'] ?? null;

  if (!$deptCode) {
    echo json_encode(['success' => false, 'error' => 'Department code required']);
    exit;
  }

  $db = new MulagoDatabase();
  $isOpen = $db->isDepartmentOpenOnDate($deptCode, $date, $time);
  
  // Get the clinic hours for more info
  $hours = $db->getClinicHoursByDepartmentCode($deptCode);

  echo json_encode([
    'success' => true,
    'is_open' => $isOpen,
    'hours' => $hours,
    'check_date' => $date ?? date('Y-m-d'),
    'check_time' => $time ?? date('H:i')
  ]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
