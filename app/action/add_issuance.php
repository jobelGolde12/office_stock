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

$division_id = isset($input['division_id']) ? intval($input['division_id']) : 0;
$inventory_id = isset($input['inventory_id']) ? intval($input['inventory_id']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;
$issuance_date = isset($input['issuance_date']) ? trim($input['issuance_date']) : date('Y-m-d');
$issued_by = isset($input['issued_by']) ? trim($input['issued_by']) : '';
$received_by = isset($input['received_by']) ? trim($input['received_by']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if (!$division_id || !$inventory_id || !$quantity) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->exec("BEGIN TRANSACTION");
    
    $stmt = $pdo->prepare("SELECT unit_price, stock_quantity FROM inventory_master WHERE id = ?");
    $stmt->execute([$inventory_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Inventory item not found');
    }
    
    if ($item['stock_quantity'] < $quantity) {
        throw new Exception('Insufficient stock. Available: ' . $item['stock_quantity']);
    }
    
    $unit_price = floatval($item['unit_price']);
    $total_value = $unit_price * $quantity;
    
    $monthKey = date('n', strtotime($issuance_date));
    $monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $month_issued = $monthNames[$monthKey - 1] ?? '';
    $year_issued = intval(date('Y', strtotime($issuance_date)));
    
    $insertStmt = $pdo->prepare("INSERT INTO issuances 
        (division_id, inventory_id, quantity, unit_price, total_value, issuance_date, month_issued, year_issued, issued_by, received_by, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $insertStmt->execute([
        $division_id,
        $inventory_id,
        $quantity,
        $unit_price,
        $total_value,
        $issuance_date,
        $month_issued,
        $year_issued,
        $issued_by,
        $received_by,
        $notes
    ]);
    
    $updateStmt = $pdo->prepare("UPDATE inventory_master SET stock_quantity = stock_quantity - ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->execute([$quantity, $inventory_id]);
    
    $pdo->exec("COMMIT");
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Issuance recorded successfully! Stock deducted: ' . $quantity
    ]);
} catch (Exception $e) {
    $pdo->exec("ROLLBACK");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
