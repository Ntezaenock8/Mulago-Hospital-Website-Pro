<?php
/**
 * Mulago Hospital — Get Specialists / Doctors
 * Retrieves all specialists from database
 */

require_once 'database.php';

header('Content-Type: application/json');

try {
  $db = new MulagoDatabase();
  $doctors = $db->getDoctors();
  
  // Format for frontend
  $specialists = array_map(function($doc) {
    // Parse available days (standardized as comma-separated)
    $days = [];
    if ($doc['available_days']) {
      // Split by comma and trim whitespace
      $parsed = array_map('trim', explode(',', $doc['available_days']));
      // Filter out empty values
      $days = array_filter($parsed);
    }
    
    // Convert is_active (1 = available, 0 = on_leave)
    $status = $doc['is_active'] ? 'available' : 'on_leave';
    
    return [
      'id' => $doc['id'],
      'name' => $doc['name'],
      'dept' => $doc['department'],
      'dept_code' => $doc['dept_code'],
      'qualifications' => $doc['qualifications'],
      'days' => $days,
      'room' => $doc['room'] ?? '',
      'status' => $status
    ];
  }, $doctors);
  
  echo json_encode($specialists);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
?>
