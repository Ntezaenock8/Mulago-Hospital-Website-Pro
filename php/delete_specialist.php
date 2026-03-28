<?php
/**
 * Mulago Hospital — Delete Specialist
 * Removes a specialist from the database
 */

require_once 'database.php';

header('Content-Type: application/json');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('POST method required');
  }

  $data = json_decode(file_get_contents('php://input'), true);
  $id = $data['id'] ?? null;

  if (!$id) {
    throw new Exception('Specialist ID is required');
  }

  $db = new MulagoDatabase();
  
  // Verify specialist exists
  $doctor = $db->getDoctor($id);
  if (!$doctor) {
    throw new Exception('Specialist not found');
  }

  // Delete the specialist
  $db->deleteDoctor($id);

  echo json_encode([
    'success' => true,
    'message' => 'Specialist deleted successfully',
    'id' => $id
  ]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
