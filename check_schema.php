<?php
try {
    $db = new PDO('sqlite:data/mulago.db');
    
    echo "=== APPOINTMENTS TABLE STRUCTURE ===\n";
    $columns = $db->query("PRAGMA table_info(appointments)")->fetchAll(PDO::FETCH_ASSOC);
    foreach($columns as $col) {
        echo "{$col['name']} ({$col['type']})\n";
    }
    
    echo "\n=== DATA IN APPOINTMENTS TABLE ===\n";
    $rows = $db->query("SELECT * FROM appointments")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        echo "Columns: " . implode(", ", array_keys($rows[0])) . "\n";
        foreach($rows as $r) {
            echo json_encode($r) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
