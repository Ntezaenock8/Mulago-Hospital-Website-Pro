<?php
// This script helps understand what localStorage might contain
// Check if there's any localStorage dump or session data

try {
    $db = new PDO('sqlite:data/mulago.db');
    
    echo "=== DATABASE STRUCTURE ===\n";
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n\n";
    
    echo "=== APPOINTMENTS WITH FULL DATA ===\n";
    $stmt = $db->prepare("
        SELECT 
            a.id, a.ref, a.reason, a.preferred_date, 
            p.first_name, p.last_name, p.nin, p.phone,
            d.name as department,
            s.code as status
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN departments d ON a.department_id = d.id
        JOIN appointment_statuses s ON a.status_id = s.id
    ");
    $stmt->execute();
    $appts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($appts as $a) {
        echo "{$a['ref']}: {$a['first_name']} {$a['last_name']} - {$a['department']} ({$a['status']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
