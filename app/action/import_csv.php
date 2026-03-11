<?php
require __DIR__ . '/../init.php';

header('Content-Type: application/json');

if (!$Ouser->is_login()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['data']) || !is_array($input['data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$data = $input['data'];
$headers = isset($input['headers']) ? array_map('strtolower', array_map('trim', $input['headers'])) : [];

$categoryMap = [
    'office supplies (common)' => 'Office Supplies (Common)',
    'office supplies' => 'Office Supplies (Common)',
    'ict and computer supplies' => 'ICT and Computer Supplies',
    'ict' => 'ICT and Computer Supplies',
    'computer' => 'ICT and Computer Supplies',
    'janitorial and sanitation supplies' => 'Janitorial and Sanitation Supplies',
    'janitorial' => 'Janitorial and Sanitation Supplies',
    'sanitation' => 'Janitorial and Sanitation Supplies',
    'semi-expendable property' => 'Semi-Expendable Property',
    'semi-expendable' => 'Semi-Expendable Property'
];

try {
    $stmt = $pdo->query("SELECT id, name FROM inventory_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $catLookup = [];
    foreach ($categories as $cat) {
        $catLookup[strtolower($cat['name'])] = $cat['id'];
    }

    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    $pdo->exec("BEGIN TRANSACTION");

    foreach ($data as $row) {
        $itemName = '';
        $categoryName = '';
        $unit = '';
        $unitPrice = 0;
        $quantity = 0;
        $description = '';

        foreach ($headers as $i => $header) {
            $value = $row[$header] ?? '';
            
            if (stripos($header, 'item') !== false || stripos($header, 'name') !== false) {
                $itemName = trim($value);
            } elseif (stripos($header, 'category') !== false) {
                $categoryName = trim($value);
            } elseif (stripos($header, 'unit') !== false) {
                $unit = trim($value);
            } elseif (stripos($header, 'price') !== false || stripos($header, 'value') !== false) {
                $unitPrice = floatval(str_replace(['₱', ',', ' '], '', $value));
            } elseif (stripos($header, 'quantity') !== false || stripos($header, 'qty') !== false || stripos($header, 'stock') !== false) {
                $quantity = intval($value);
            } elseif (stripos($header, 'description') !== false || stripos($header, 'desc') !== false) {
                $description = trim($value);
            }
        }

        if (empty($itemName)) {
            $skipped++;
            continue;
        }

        $normalizedCat = strtolower($categoryName);
        $categoryId = null;
        
        if (isset($categoryMap[$normalizedCat])) {
            $catName = $categoryMap[$normalizedCat];
            $categoryId = $catLookup[$catName] ?? null;
        } else {
            foreach ($catLookup as $catKey => $catId) {
                if (strpos($normalizedCat, $catKey) !== false) {
                    $categoryId = $catId;
                    break;
                }
            }
        }

        if (!$categoryId) {
            $categoryId = $catLookup['office supplies (common)'] ?? null;
        }

        $checkStmt = $pdo->prepare("SELECT id FROM inventory_master WHERE LOWER(item_name) = LOWER(?)");
        $checkStmt->execute([$itemName]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $updateStmt = $pdo->prepare("UPDATE inventory_master SET 
                category_id = ?, unit_measure = ?, unit_price = ?, stock_quantity = ?, description = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $updateStmt->execute([
                $categoryId,
                $unit,
                $unitPrice,
                $quantity,
                $description,
                $existing['id']
            ]);
            $updated++;
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO inventory_master 
                (item_name, category_id, unit_measure, unit_price, stock_quantity, description) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([
                $itemName,
                $categoryId,
                $unit,
                $unitPrice,
                $quantity,
                $description
            ]);
            $inserted++;
        }
    }

    $pdo->exec("COMMIT");

    echo json_encode([
        'status' => 'ok',
        'message' => 'Import completed successfully!',
        'summary' => [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $inserted + $updated + $skipped
        ]
    ]);
} catch (Exception $e) {
    $pdo->exec("ROLLBACK");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
