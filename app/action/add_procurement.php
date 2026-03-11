<?php
require __DIR__ . '/../init.php';

header('Content-Type: application/json');

if (!$Ouser->is_login()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$inventory_id = isset($input['inventory_id']) ? intval($input['inventory_id']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;
$unit_price = isset($input['unit_price']) ? floatval($input['unit_price']) : 0;
$delivery_date = isset($input['delivery_date']) ? trim($input['delivery_date']) : date('Y-m-d');
$supplier_name = isset($input['supplier_name']) ? trim($input['supplier_name']) : '';
$po_number = isset($input['po_number']) ? trim($input['po_number']) : '';
$received_by = isset($input['received_by']) ? trim($input['received_by']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if (!$inventory_id || !$quantity) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->exec("BEGIN TRANSACTION");
    
    $total_value = $unit_price * $quantity;
    
    $insertStmt = $pdo->prepare("INSERT INTO procurements 
        (inventory_id, quantity, unit_price, total_value, delivery_date, supplier_name, po_number, received_by, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $insertStmt->execute([
        $inventory_id,
        $quantity,
        $unit_price,
        $total_value,
        $delivery_date,
        $supplier_name,
        $po_number,
        $received_by,
        $notes
    ]);
    
    $updateStmt = $pdo->prepare("UPDATE inventory_master SET stock_quantity = stock_quantity + ?, unit_price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->execute([$quantity, $unit_price, $inventory_id]);
    
    $pdo->exec("COMMIT");
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Delivery recorded successfully! Stock added: ' . $quantity
    ]);
} catch (Exception $e) {
    $pdo->exec("ROLLBACK");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
