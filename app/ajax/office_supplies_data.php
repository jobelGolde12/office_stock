<?php
require_once '../init.php';

header('Content-Type: application/json; charset=utf-8');

if (!$Ouser->is_login()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

try {
    $rows = [];

    // Preferred query with category and stock join.
    try {
        $sql = "SELECT
                    os.id,
                    os.category_id,
                    os.item_name,
                    os.description,
                    os.unit_cost,
                    os.created_at,
                    os.updated_at,
                    c.name AS category,
                    COALESCE(s.quantity_available, 0) AS stocks
                FROM office_supplies os
                LEFT JOIN categories c ON c.id = os.category_id
                LEFT JOIN stock s ON s.office_supply_id = os.id
                ORDER BY os.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback when stock table is unavailable in current schema.
        $sql = "SELECT
                    os.id,
                    os.category_id,
                    os.item_name,
                    os.description,
                    os.unit_cost,
                    os.created_at,
                    os.updated_at,
                    c.name AS category,
                    0 AS stocks
                FROM office_supplies os
                LEFT JOIN categories c ON c.id = os.category_id
                ORDER BY os.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'status' => 'ok',
        'data' => $rows
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to load office supplies data',
        'debug' => $e->getMessage()
    ]);
}
