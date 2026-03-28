<?php
/**
 * Mulago Hospital — Save Specialist (Add/Edit)
 * Creates or updates a specialist in the database
 */

require_once 'database.php';

header('Content-Type: application/json');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('POST method required');
  }

  $data = json_decode(file_get_contents('php://input'), true);
  
  $id = $data['id'] ?? null;
  $name = trim($data['name'] ?? '');
  $dept_code = trim($data['dept_code'] ?? '');
  $qualifications = trim($data['qualifications'] ?? '');
  $room = trim($data['room'] ?? '');
  // Convert days array to comma-separated string
  $available_days = implode(',', array_filter($data['days'] ?? []));
  // Convert status (available = 1, on_leave = 0)
  $is_active = ($data['status'] ?? 'available') === 'available' ? 1 : 0;

  if (!$name || !$dept_code || !$qualifications) {
    throw new Exception('Name, Department Code, and Qualifications are required');
  }

  if (empty($available_days)) {
    throw new Exception('At least one available day must be selected');
  }

  $db = new MulagoDatabase();

  if ($id) {
    // Update existing
    $doctor = $db->getDoctor($id);
    if (!$doctor) {
      throw new Exception('Specialist not found');
    }
    $db->updateDoctor($id, $name, $dept_code, $qualifications, '', $available_days, $room, $is_active);
    $message = 'Specialist updated successfully';
  } else {
    // Create new
    $newId = $db->createDoctor($name, $dept_code, $qualifications, '', $available_days, $room, $is_active);
    $id = $newId;
    $message = 'Specialist added successfully';
  }

  echo json_encode([
    'success' => true,
    'message' => $message,
    'id' => $id
  ]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
