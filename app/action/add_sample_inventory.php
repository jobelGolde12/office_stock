<?php
require __DIR__ . '/../init.php';

$sampleItems = [
    ['Bond Paper (A4)', 1, 'ream', 280.00, 50, 'Standard 80gsm white bond paper'],
    ['Bond Paper (Legal)', 1, 'ream', 350.00, 30, 'Standard 80gsm legal size'],
    ['Sign Pen (Black)', 1, 'piece', 15.00, 100, 'Black signing pen'],
    ['Sign Pen (Blue)', 1, 'piece', 15.00, 100, 'Blue signing pen'],
    ['Staple Wires', 1, 'box', 45.00, 50, 'Standard staple wires'],
    ['File Folders', 1, 'piece', 12.00, 200, 'Manila file folder'],
    ['Expanding Envelope', 1, 'piece', 195.07, 25, 'Legal size expanding envelope'],
    ['Ink Cartridge (Black)', 2, 'piece', 850.00, 20, 'Printer ink cartridge'],
    ['Ink Cartridge (Color)', 2, 'piece', 950.00, 15, 'Color printer ink cartridge'],
    ['Printer Toner', 2, 'piece', 2500.00, 10, 'Laser printer toner'],
    ['USB Flash Drive 16GB', 2, 'piece', 350.00, 25, '16GB USB storage'],
    ['Computer Mouse', 2, 'piece', 250.00, 20, 'USB computer mouse'],
    ['External Hard Drive 1TB', 2, 'piece', 3500.00, 5, '1TB external HDD'],
    ['Alcohol (Gallon)', 3, 'gallon', 450.00, 20, '70% isopropyl alcohol'],
    ['Alcohol (500ml)', 3, 'piece', 120.00, 50, '500ml alcohol bottle'],
    ['Tissue Paper', 3, 'pack', 85.00, 100, 'Facial tissue pack'],
    ['Air Freshener', 3, 'piece', 126.67, 30, 'Automatic air freshener'],
    ['Cleaning Solution', 3, 'gallon', 380.00, 15, 'Multi-purpose cleaner'],
    ['Heavy Duty Stapler', 4, 'piece', 597.87, 10, 'Heavy duty office stapler'],
    ['Puncher', 4, 'piece', 225.00, 15, '2-hole paper puncher'],
    ['Waste Basket', 4, 'piece', 44.34, 30, 'Plastic waste basket'],
];

try {
    $pdo->exec("BEGIN TRANSACTION");
    
    foreach ($sampleItems as $item) {
        $stmt = $pdo->prepare("INSERT INTO inventory_master (item_name, category_id, unit_measure, unit_price, stock_quantity, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($item);
    }
    
    $pdo->exec("COMMIT");
    echo "Sample inventory data added successfully!";
} catch (Exception $e) {
    $pdo->exec("ROLLBACK");
    echo "Error: " . $e->getMessage();
}
