<?php
require __DIR__ . '/../init.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT stock_quantity, unit_price FROM inventory_master WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    echo json_encode([
        'status' => 'ok',
        'stock' => $item['stock_quantity'],
        'price' => $item['unit_price']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
