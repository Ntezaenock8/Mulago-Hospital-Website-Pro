<?php
try {
    $db = new PDO('sqlite:data/mulago.db');
    $stmt = $db->query('SELECT COUNT(*) as cnt FROM appointments');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "Appointments in database: $count\n";
    
    if ($count > 0) {
        echo "\nAppointments:\n";
        $rows = $db->query('SELECT id, ref, first_name, last_name, nin FROM appointments')->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $row) {
            echo "  [{$row['id']}] {$row['ref']} - {$row['first_name']} {$row['last_name']} ({$row['nin']})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
