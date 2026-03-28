<?php
/**
 * Mulago Hospital — Manual Database Seeding Script
 * 
 * This script seeds the database with initial data (departments, specialists, statuses, etc.)
 * Run this ONLY when you want to seed the database, NOT automatically on every connection.
 * 
 * Usage: php seed_database.php
 * 
 * ⚠️ WARNING: This will overwrite existing data. Only run once on fresh database setup.
 */

require_once 'database.php';

header('Content-Type: application/json');

try {
  // Verify this is being called intentionally (not accidentally)
  echo json_encode([
    'status' => 'seeding_database',
    'message' => 'Starting database seeding...',
    'timestamp' => date('Y-m-d H:i:s')
  ]);
  
  $db = new MulagoDatabase();
  $result = $db->seedDatabase();
  
  if ($result) {
    echo json_encode([
      'success' => true,
      'message' => 'Database seeded successfully!',
      'details' => 'All lookup tables, specialists, and clinic hours have been initialized.',
      'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
  } else {
    throw new Exception('Seeding failed');
  }
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Seeding failed: ' . $e->getMessage(),
    'timestamp' => date('Y-m-d H:i:s')
  ], JSON_PRETTY_PRINT);
}
?>
