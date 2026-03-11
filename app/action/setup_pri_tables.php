<?php
require __DIR__ . '/../init.php';

$tables = [
    'divisions' => "CREATE TABLE IF NOT EXISTS divisions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        description TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    'inventory_categories' => "CREATE TABLE IF NOT EXISTS inventory_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    'inventory_master' => "CREATE TABLE IF NOT EXISTS inventory_master (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_name TEXT NOT NULL,
        category_id INTEGER,
        unit_measure TEXT NOT NULL,
        unit_price REAL NOT NULL DEFAULT 0,
        stock_quantity INTEGER NOT NULL DEFAULT 0,
        min_stock_level INTEGER DEFAULT 5,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES inventory_categories(id)
    )",
    
    'issuances' => "CREATE TABLE IF NOT EXISTS issuances (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        division_id INTEGER NOT NULL,
        inventory_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        unit_price REAL NOT NULL,
        total_value REAL NOT NULL,
        issuance_date DATE NOT NULL,
        month_issued TEXT NOT NULL,
        year_issued INTEGER NOT NULL,
        issued_by TEXT,
        received_by TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (division_id) REFERENCES divisions(id),
        FOREIGN KEY (inventory_id) REFERENCES inventory_master(id)
    )",
    
    'procurements' => "CREATE TABLE IF NOT EXISTS procurements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inventory_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        unit_price REAL NOT NULL,
        total_value REAL NOT NULL,
        delivery_date DATE NOT NULL,
        supplier_name TEXT,
        po_number TEXT,
        received_by TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (inventory_id) REFERENCES inventory_master(id)
    )"
];

$defaultDivisions = [
    ['AFS', 'Administrative and Finance Section', 'Handles administrative and finance operations'],
    ['CAD', 'Construction and Development', 'Construction and development related tasks'],
    ['ROS', 'Regional Operations Section', 'Regional operations management'],
    ['RSEFS', 'Research, Extension and Fellowships Section', 'Research and extension activities'],
    ['CATS', 'Technical Services Section', 'Technical services and support'],
    ['SSS', 'Social Security Services', 'Social security related services'],
    ['RDD', 'Research and Development Division', 'Research and development activities']
];

$defaultCategories = [
    ['Office Supplies (Common)', 'Common office materials required for daily administrative work'],
    ['ICT and Computer Supplies', 'Supplies related to computer equipment and IT resources'],
    ['Janitorial and Sanitation Supplies', 'Materials used for cleanliness and sanitation'],
    ['Semi-Expendable Property', 'Items not consumed but not classified as permanent assets']
];

$results = [];

try {
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            $results[] = "Table '$tableName' created successfully";
        } catch (Exception $e) {
            $results[] = "Error creating table '$tableName': " . $e->getMessage();
        }
    }

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM divisions");
    $row = $stmt->fetch();
    if ($row['cnt'] == 0) {
        foreach ($defaultDivisions as $div) {
            $pdo->exec("INSERT INTO divisions (code, name, description) VALUES ('{$div[0]}', '{$div[1]}', '{$div[2]}')");
        }
        $results[] = "Default divisions inserted";
    }

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM inventory_categories");
    $row = $stmt->fetch();
    if ($row['cnt'] == 0) {
        foreach ($defaultCategories as $cat) {
            $pdo->exec("INSERT INTO inventory_categories (name, description) VALUES ('{$cat[0]}', '{$cat[1]}')");
        }
        $results[] = "Default categories inserted";
    }

    echo json_encode(['status' => 'ok', 'message' => implode("\n", $results)]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
