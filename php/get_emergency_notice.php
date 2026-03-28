<?php
/**
 * Mulago Hospital — Get Emergency Notice API
 * Returns current emergency notice if active
 */

header('Content-Type: application/json');

require __DIR__ . '/database.php';

try {
  $db = new MulagoDatabase();
  $notice = $db->getEmergencyNotice();
  echo json_encode(['success' => true, 'data' => $notice]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
