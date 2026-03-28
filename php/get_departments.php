<?php
/**
 * Mulago Hospital — Get Departments API
 * Returns all departments for frontend dropdown population
 */

require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
  $db = new MulagoDatabase();
  $departments = $db->getDepartments();
  
  echo json_encode($departments);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
?>
