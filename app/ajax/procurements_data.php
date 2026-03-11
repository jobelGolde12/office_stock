<?php
require __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT 
                p.*,
                im.item_name
            FROM procurements p
            LEFT JOIN inventory_master im ON p.inventory_id = im.id
            ORDER BY p.delivery_date DESC, p.id DESC
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
