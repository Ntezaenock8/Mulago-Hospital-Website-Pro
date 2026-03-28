<?php
require_once __DIR__ . '/php/Database.php';

try {
    // Create DB instance - this will initialize schema on first run
    $db = new MulagoDatabase();
    
    // Query the database directly
    $pdo = new PDO('sqlite:' . __DIR__ . '/data/mulago.db');
    
    echo "=== DATABASE SCHEMA ===\n";
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n\n";
    
    echo "=== APPOINTMENTS COUNT ===\n";
    $count = $pdo->query("SELECT COUNT(*) as count FROM appointments")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total: $count\n";
    
    if ($count > 0) {
        echo "\nDetails:\n";
        $appts = $pdo->query("SELECT ref, first_name, last_name FROM appointments")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($appts as $a) {
            echo "  - {$a['ref']}: {$a['first_name']} {$a['last_name']}\n";
        }
    }
    
    $db->close();
    echo "\n✓ Database verified successfully\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

