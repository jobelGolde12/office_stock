<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/connection.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "This script must be run from CLI.\n";
    exit(1);
}

$categorySeed = [
    'Sanitation & Hygiene',
    'Paper Products',
    'Filing & Storage',
    'Desk Essentials',
    'Writing & Correction',
    'Adhesives & Tapes',
    'Organization Tools',
    'Printer & Ink Supplies',
];

$suppliesSeed = [
    ['item_name' => 'Alcohol, Ethyl, 500ml', 'description' => 'Ethyl alcohol disinfectant, office and pantry use.', 'unit_cost' => 55.62, 'category' => 'Sanitation & Hygiene', 'quantity_available' => 48, 'reorder_level' => 20],
    ['item_name' => 'Calculator, compact', 'description' => 'Compact desktop calculator for daily computations.', 'unit_cost' => 220.49, 'category' => 'Desk Essentials', 'quantity_available' => 18, 'reorder_level' => 8],
    ['item_name' => 'Cartolina, assorted colors', 'description' => 'Colored cartolina sheets for presentations and signage.', 'unit_cost' => 84.98, 'category' => 'Paper Products', 'quantity_available' => 62, 'reorder_level' => 25],
    ['item_name' => 'Clip, backfold, 50mm', 'description' => 'Heavy-duty 50mm binder clips.', 'unit_cost' => 63.83, 'category' => 'Organization Tools', 'quantity_available' => 96, 'reorder_level' => 35],
    ['item_name' => 'Envelope, Expanding, Kraft', 'description' => 'Expandable kraft envelope for bulky documents.', 'unit_cost' => 9.75, 'category' => 'Filing & Storage', 'quantity_available' => 140, 'reorder_level' => 60],
    ['item_name' => 'Folder with Tab, A4', 'description' => 'A4 folder with index tab for file grouping.', 'unit_cost' => 3.85, 'category' => 'Filing & Storage', 'quantity_available' => 230, 'reorder_level' => 80],
    ['item_name' => 'Folder with TAB, Legal', 'description' => 'Legal-size folder with tab.', 'unit_cost' => 4.20, 'category' => 'Filing & Storage', 'quantity_available' => 180, 'reorder_level' => 75],
    ['item_name' => 'Folder, L-type, A4', 'description' => 'Transparent L-type A4 folder.', 'unit_cost' => 3.85, 'category' => 'Filing & Storage', 'quantity_available' => 215, 'reorder_level' => 85],
    ['item_name' => 'Paper, Multicopy, 80gsm, A4', 'description' => 'A4 copier paper, 80gsm, 500 sheets.', 'unit_cost' => 213.86, 'category' => 'Paper Products', 'quantity_available' => 120, 'reorder_level' => 50],
    ['item_name' => 'Paper, Multicopy, 80gsm, Legal', 'description' => 'Legal-size copier paper, 80gsm, 500 sheets.', 'unit_cost' => 227.64, 'category' => 'Paper Products', 'quantity_available' => 88, 'reorder_level' => 40],
    ['item_name' => 'Scissors, Symmetrical / Asymmetrical', 'description' => 'General-purpose office scissors.', 'unit_cost' => 37.23, 'category' => 'Desk Essentials', 'quantity_available' => 44, 'reorder_level' => 15],
    ['item_name' => 'Staple Remover, plier type', 'description' => 'Plier-style staple remover.', 'unit_cost' => 35.89, 'category' => 'Desk Essentials', 'quantity_available' => 37, 'reorder_level' => 12],
    ['item_name' => 'Tissue, Interfolded Paper Towel', 'description' => 'Interfolded paper towels for washroom and pantry.', 'unit_cost' => 34.31, 'category' => 'Sanitation & Hygiene', 'quantity_available' => 110, 'reorder_level' => 45],
    ['item_name' => 'Toilet Tissue Paper, 2 ply', 'description' => '2-ply toilet tissue roll.', 'unit_cost' => 8.41, 'category' => 'Sanitation & Hygiene', 'quantity_available' => 260, 'reorder_level' => 100],

    ['item_name' => 'Ballpen, black, 0.5mm', 'description' => 'Fine-tip black ballpen.', 'unit_cost' => 12.50, 'category' => 'Writing & Correction', 'quantity_available' => 420, 'reorder_level' => 150],
    ['item_name' => 'Ballpen, blue, 0.5mm', 'description' => 'Fine-tip blue ballpen.', 'unit_cost' => 12.50, 'category' => 'Writing & Correction', 'quantity_available' => 380, 'reorder_level' => 150],
    ['item_name' => 'Permanent Marker, black', 'description' => 'Quick-dry black permanent marker.', 'unit_cost' => 32.90, 'category' => 'Writing & Correction', 'quantity_available' => 76, 'reorder_level' => 30],
    ['item_name' => 'Whiteboard Marker, assorted', 'description' => 'Dry erase marker set, assorted colors.', 'unit_cost' => 58.75, 'category' => 'Writing & Correction', 'quantity_available' => 52, 'reorder_level' => 20],
    ['item_name' => 'Correction Tape, 5mm', 'description' => 'Single-line correction tape.', 'unit_cost' => 29.40, 'category' => 'Writing & Correction', 'quantity_available' => 90, 'reorder_level' => 32],
    ['item_name' => 'Notebook, spiral, A5', 'description' => 'A5 spiral notebook, 80 leaves.', 'unit_cost' => 64.00, 'category' => 'Paper Products', 'quantity_available' => 134, 'reorder_level' => 45],
    ['item_name' => 'Sticky Notes, 3x3, neon', 'description' => '3x3 sticky notes, assorted neon colors.', 'unit_cost' => 42.30, 'category' => 'Desk Essentials', 'quantity_available' => 125, 'reorder_level' => 50],
    ['item_name' => 'Masking Tape, 24mm', 'description' => '24mm masking tape for labeling and bundling.', 'unit_cost' => 27.80, 'category' => 'Adhesives & Tapes', 'quantity_available' => 145, 'reorder_level' => 55],
    ['item_name' => 'Packaging Tape, 48mm', 'description' => 'Clear packaging tape, 48mm x 50m.', 'unit_cost' => 54.20, 'category' => 'Adhesives & Tapes', 'quantity_available' => 99, 'reorder_level' => 35],
    ['item_name' => 'Glue Stick, 21g', 'description' => 'Acid-free glue stick, 21g.', 'unit_cost' => 18.75, 'category' => 'Adhesives & Tapes', 'quantity_available' => 160, 'reorder_level' => 60],
    ['item_name' => 'Stapler, heavy duty', 'description' => 'Heavy-duty desktop stapler.', 'unit_cost' => 198.00, 'category' => 'Desk Essentials', 'quantity_available' => 24, 'reorder_level' => 10],
    ['item_name' => 'Staple Wire, No.35', 'description' => 'Staple wire strips compatible with No.35 staplers.', 'unit_cost' => 22.60, 'category' => 'Desk Essentials', 'quantity_available' => 205, 'reorder_level' => 80],
    ['item_name' => 'Paper Clips, vinyl coated, 33mm', 'description' => 'Vinyl-coated paper clips, 33mm.', 'unit_cost' => 16.95, 'category' => 'Organization Tools', 'quantity_available' => 310, 'reorder_level' => 120],
    ['item_name' => 'Index Tabs, writable, 5-color', 'description' => 'Writable index tabs for quick sectioning.', 'unit_cost' => 47.10, 'category' => 'Organization Tools', 'quantity_available' => 95, 'reorder_level' => 35],
    ['item_name' => 'Document Tray, 3-layer', 'description' => 'Stackable 3-layer document tray.', 'unit_cost' => 315.00, 'category' => 'Organization Tools', 'quantity_available' => 12, 'reorder_level' => 5],
    ['item_name' => 'Ink Cartridge, Black, 678', 'description' => 'Black printer ink cartridge model 678.', 'unit_cost' => 745.00, 'category' => 'Printer & Ink Supplies', 'quantity_available' => 14, 'reorder_level' => 6],
    ['item_name' => 'Ink Cartridge, Tri-color, 678', 'description' => 'Tri-color printer ink cartridge model 678.', 'unit_cost' => 812.00, 'category' => 'Printer & Ink Supplies', 'quantity_available' => 10, 'reorder_level' => 5],
    ['item_name' => 'Thermal Paper Roll, 57mm', 'description' => 'Thermal roll for POS and receipt printers.', 'unit_cost' => 31.25, 'category' => 'Paper Products', 'quantity_available' => 72, 'reorder_level' => 25],
];

$stats = [
    'categories_inserted' => 0,
    'categories_existing' => 0,
    'supplies_inserted' => 0,
    'supplies_updated' => 0,
    'stock_inserted' => 0,
    'stock_updated' => 0,
];

function fetchOneAssoc($pdo, $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function executeStmt($pdo, $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

try {
    $categoryIdByName = [];

    foreach ($categorySeed as $categoryName) {
        $found = fetchOneAssoc(
            $pdo,
            'SELECT id, name FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1',
            [$categoryName]
        );

        if ($found) {
            $categoryIdByName[$categoryName] = (int) $found['id'];
            $stats['categories_existing']++;
            continue;
        }

        executeStmt($pdo, 'INSERT INTO categories (name) VALUES (?)', [$categoryName]);
        $inserted = fetchOneAssoc(
            $pdo,
            'SELECT id FROM categories WHERE LOWER(name) = LOWER(?) ORDER BY id DESC LIMIT 1',
            [$categoryName]
        );

        if (!$inserted) {
            throw new RuntimeException('Failed to insert category: ' . $categoryName);
        }

        $categoryIdByName[$categoryName] = (int) $inserted['id'];
        $stats['categories_inserted']++;
    }

    foreach ($suppliesSeed as $item) {
        $categoryName = $item['category'];
        if (!isset($categoryIdByName[$categoryName])) {
            throw new RuntimeException('Category not found in seed map: ' . $categoryName);
        }

        $categoryId = $categoryIdByName[$categoryName];
        $existingSupply = fetchOneAssoc(
            $pdo,
            'SELECT id FROM office_supplies WHERE LOWER(item_name) = LOWER(?) LIMIT 1',
            [$item['item_name']]
        );

        if ($existingSupply) {
            $officeSupplyId = (int) $existingSupply['id'];
            executeStmt(
                $pdo,
                'UPDATE office_supplies
                 SET category_id = ?, description = ?, unit_cost = ?, updated_at = datetime("now")
                 WHERE id = ?',
                [$categoryId, $item['description'], $item['unit_cost'], $officeSupplyId]
            );
            $stats['supplies_updated']++;
        } else {
            executeStmt(
                $pdo,
                'INSERT INTO office_supplies (category_id, item_name, description, unit_cost)
                 VALUES (?, ?, ?, ?)',
                [$categoryId, $item['item_name'], $item['description'], $item['unit_cost']]
            );

            $insertedSupply = fetchOneAssoc(
                $pdo,
                'SELECT id FROM office_supplies WHERE LOWER(item_name) = LOWER(?) ORDER BY id DESC LIMIT 1',
                [$item['item_name']]
            );

            if (!$insertedSupply) {
                throw new RuntimeException('Failed to insert office supply: ' . $item['item_name']);
            }

            $officeSupplyId = (int) $insertedSupply['id'];
            $stats['supplies_inserted']++;
        }

        $existingStock = fetchOneAssoc(
            $pdo,
            'SELECT id FROM stock WHERE office_supply_id = ? LIMIT 1',
            [$officeSupplyId]
        );

        if ($existingStock) {
            executeStmt(
                $pdo,
                'UPDATE stock
                 SET quantity_available = ?, reorder_level = ?, last_updated = datetime("now")
                 WHERE office_supply_id = ?',
                [$item['quantity_available'], $item['reorder_level'], $officeSupplyId]
            );
            $stats['stock_updated']++;
        } else {
            executeStmt(
                $pdo,
                'INSERT INTO stock (office_supply_id, quantity_available, reorder_level)
                 VALUES (?, ?, ?)',
                [$officeSupplyId, $item['quantity_available'], $item['reorder_level']]
            );
            $stats['stock_inserted']++;
        }
    }

    echo "Seed completed successfully.\n";
    echo "Categories: {$stats['categories_inserted']} inserted, {$stats['categories_existing']} existing\n";
    echo "Office supplies: {$stats['supplies_inserted']} inserted, {$stats['supplies_updated']} updated\n";
    echo "Stock rows: {$stats['stock_inserted']} inserted, {$stats['stock_updated']} updated\n";
    echo 'Total seeded supply entries processed: ' . count($suppliesSeed) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
