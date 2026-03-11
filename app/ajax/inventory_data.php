<?php
require __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT 
                im.*,
                ic.name as category_name
            FROM inventory_master im
            LEFT JOIN inventory_categories ic ON im.category_id = ic.id
            ORDER BY im.item_name ASC";
    
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
