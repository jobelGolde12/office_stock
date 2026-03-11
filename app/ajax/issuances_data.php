<?php
require __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT 
                i.*,
                d.code as division_code,
                d.name as division_name,
                im.item_name
            FROM issuances i
            LEFT JOIN divisions d ON i.division_id = d.id
            LEFT JOIN inventory_master im ON i.inventory_id = im.id
            ORDER BY i.issuance_date DESC, i.id DESC
            LIMIT 50";
    
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'ok',
        'data' => $items,
        'total' => count($items)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
