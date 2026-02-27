<?php
require_once 'app/init.php';

// Check authentication
if ($Ouser->is_login() == false) {
    header("location:login.php");
    exit();
}

// Get current date and time
date_default_timezone_set('Asia/Manila'); // Adjust timezone as needed
$currentTime = date('h:i A');
$currentDate = date('l, F j, Y');
?>

<!DOCTYPE HTML>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            color: #1e293b;
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* Content Wrapper */
        .content-wrapper {
            padding: 30px 24px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Header Styles */
        .content-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .breadcrumb-custom {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: white;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .breadcrumb-custom a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .breadcrumb-custom a:hover {
            color: #764ba2;
        }

        .date-time {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 25px;
            border-radius: 50px;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            color: #667eea;
        }

        .stat-icon.success {
            background: linear-gradient(135deg, #10b98120 0%, #05966920 100%);
            color: #10b981;
        }

        .stat-icon.info {
            background: linear-gradient(135deg, #3b82f620 0%, #2563eb20 100%);
            color: #3b82f6;
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, #f59e0b20 0%, #d9770620 100%);
            color: #f59e0b;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
            line-height: 1.2;
        }

        .stat-label {
            color: #64748b;
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .stat-trend {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f8fafc;
            border-radius: 50px;
            width: fit-content;
        }

        .stat-trend.up {
            color: #10b981;
        }

        .stat-trend.down {
            color: #ef4444;
        }

        /* Chart Cards */
        .chart-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(0,0,0,0.03);
            height: 100%;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .chart-period {
            padding: 6px 15px;
            background: #f8fafc;
            border-radius: 50px;
            font-size: 0.85rem;
            color: #64748b;
        }

        /* Table Styles */
        .table-card {
            background: white;
            border-radius: 24px;
            padding: 0;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .table-header {
            padding: 20px 25px;
            background: white;
            border-bottom: 2px solid #f1f5f9;
        }

        .table-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-custom {
            padding: 6px 15px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .badge-custom.primary {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            color: #667eea;
        }

        .badge-custom.success {
            background: #10b98120;
            color: #10b981;
        }

        .badge-custom.warning {
            background: #f59e0b20;
            color: #f59e0b;
        }

        .badge-custom.danger {
            background: #ef444420;
            color: #ef4444;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 25px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table tbody td {
            padding: 15px 25px;
            vertical-align: middle;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-badge.in-stock {
            background: #10b98120;
            color: #10b981;
        }

        .status-badge.low-stock {
            background: #f59e0b20;
            color: #f59e0b;
        }

        .status-badge.out-of-stock {
            background: #ef444420;
            color: #ef4444;
        }

        /* Action Buttons */
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 3px;
        }

        .action-btn.view {
            background: #3b82f6;
        }

        .action-btn.edit {
            background: #10b981;
        }

        .action-btn.delete {
            background: #ef4444;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            color: white;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .quick-action-item {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            flex: 1;
            min-width: 120px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .quick-action-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .quick-action-item:hover i,
        .quick-action-item:hover span {
            color: white;
        }

        .quick-action-item i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .quick-action-item span {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #1e293b;
            transition: color 0.3s ease;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .content-wrapper {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .date-time {
                margin-top: 15px;
                width: 100%;
                justify-content: center;
            }
            
            .quick-actions {
                margin-top: 20px;
            }
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
            
            .table-header {
                padding: 15px 20px;
            }
            
            .table thead th,
            .table tbody td {
                padding: 12px 15px;
                font-size: 0.85rem;
            }
            
            .action-btn {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
            
            .quick-action-item {
                min-width: calc(50% - 7.5px);
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.5rem;
                text-align: center;
            }
            
            .breadcrumb-custom {
                justify-content: center;
            }
            
            .date-time {
                flex-direction: column;
                gap: 5px;
                padding: 15px;
            }
            
            .stat-card {
                margin-bottom: 15px;
            }
            
            .quick-action-item {
                min-width: 100%;
            }
            
            .table-responsive {
                border-radius: 24px;
            }
        }

        /* Loading Animation */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .content-wrapper {
                padding: 0;
            }
            
            .stat-card {
                break-inside: avoid;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
                color: #e2e8f0;
            }
            
            .stat-card,
            .chart-card,
            .table-card {
                background: #1e293b;
                border-color: #334155;
            }
            
            .stat-value,
            .table tbody td {
                color: #f1f5f9;
            }
            
            .table thead th {
                background: #334155;
                color: #cbd5e1;
            }
            
            .table tbody tr:hover {
                background: #334155;
            }
        }
    </style>
    
    <title>Modern Dashboard | Inventory Management</title>
</head>
<body>
    <div class="content-wrapper">
        <!-- Header Section -->
        <div class="content-header">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-7">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h1 class="page-title">
                            <i class="fas fa-chart-pie me-2" style="background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                            Dashboard Overview
                        </h1>
                        <div class="breadcrumb-custom">
                            <a href="#"><i class="fas fa-home me-1"></i>Home</a>
                            <i class="fas fa-chevron-right" style="color: #cbd5e1; font-size: 0.8rem;"></i>
                            <span style="color: #94a3b8;">Dashboard</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-5">
                    <div class="date-time no-print">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $currentTime; ?></span>
                        <i class="fas fa-calendar-alt ms-2"></i>
                        <span><?php echo $currentDate; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions mb-4 no-print" data-aos="fade-up">
            <div class="quick-action-item" onclick="window.location.href='add-supply.php'">
                <i class="fas fa-plus-circle"></i>
                <span>Add Supply</span>
            </div>
            <div class="quick-action-item" onclick="window.location.href='categories.php'">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </div>
            <div class="quick-action-item" onclick="window.location.href='reports.php'">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </div>
            <div class="quick-action-item" onclick="window.location.href='settings.php'">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <?php
            // Fetch stats
            $stats = [];
            
            // Categories count
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
            $stats['categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Office supplies count
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM office_supplies");
            $stats['supplies'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Total stock
            $stmt = $pdo->query("SELECT SUM(quantity_available) as total FROM stock");
            $stats['total_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Low stock items
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM stock WHERE quantity_available <= reorder_level");
            $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Total inventory value
            $stmt = $pdo->query("SELECT SUM(s.quantity_available * o.unit_cost) as total_value 
                                 FROM stock s JOIN office_supplies o ON s.office_supply_id = o.id");
            $totalValue = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
            
            // Average stock
            $stmt = $pdo->query("SELECT AVG(quantity_available) as avg_stock FROM stock");
            $avgStock = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_stock'] ?? 0);
            
            // Healthy stock
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM stock WHERE quantity_available > reorder_level");
            $healthyStock = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            ?>
            
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['categories']; ?></div>
                    <div class="stat-label">Total Categories</div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>Active categories</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['supplies']; ?></div>
                    <div class="stat-label">Office Supplies</div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>Total items</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_stock']); ?></div>
                    <div class="stat-label">Total Stock</div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>Units available</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-trend down">
                        <i class="fas fa-arrow-down"></i>
                        <span>Need restock</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-dollar-sign me-2" style="color: #10b981;"></i>
                            Inventory Value
                        </div>
                        <div class="chart-period">Current</div>
                    </div>
                    <div class="stat-value" style="font-size: 2rem; margin-bottom: 15px;">
                        $<?php echo number_format($totalValue, 2); ?>
                    </div>
                    <div style="height: 100px; position: relative;">
                        <canvas id="valueChart"></canvas>
                    </div>
                    <p class="text-muted mt-3 mb-0" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle me-1"></i>
                        Estimated total inventory value
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-chart-line me-2" style="color: #3b82f6;"></i>
                            Average Stock
                        </div>
                        <div class="chart-period">Per Item</div>
                    </div>
                    <div class="stat-value" style="font-size: 2rem; margin-bottom: 15px;">
                        <?php echo $avgStock; ?>
                    </div>
                    <div style="height: 100px; position: relative;">
                        <canvas id="stockChart"></canvas>
                    </div>
                    <p class="text-muted mt-3 mb-0" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle me-1"></i>
                        Average units per item
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <i class="fas fa-check-circle me-2" style="color: #f59e0b;"></i>
                            Stock Health
                        </div>
                        <div class="chart-period">Status</div>
                    </div>
                    <div class="stat-value" style="font-size: 2rem; margin-bottom: 15px;">
                        <?php echo $healthyStock; ?>
                    </div>
                    <div style="height: 100px; position: relative;">
                        <canvas id="healthChart"></canvas>
                    </div>
                    <p class="text-muted mt-3 mb-0" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle me-1"></i>
                        Items above reorder level
                    </p>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="row g-4 mb-4">
            <!-- Low Stock Alerts -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="table-card">
                    <div class="table-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="table-title">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Low Stock Alerts
                        </div>
                        <span class="badge-custom warning">Needs Attention</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Available</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT s.id, s.quantity_available, s.reorder_level, 
                                           o.item_name, c.name as category_name
                                    FROM stock s
                                    JOIN office_supplies o ON s.office_supply_id = o.id
                                    JOIN categories c ON o.category_id = c.id
                                    WHERE s.quantity_available <= s.reorder_level
                                    ORDER BY s.quantity_available ASC
                                    LIMIT 5
                                ");
                                $stmt->execute();
                                $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (empty($lowStockItems)) {
                                    echo '<tr><td colspan="6" class="text-center py-4">No low stock items</td></tr>';
                                } else {
                                    foreach ($lowStockItems as $item) {
                                        $statusClass = $item['quantity_available'] <= $item['reorder_level'] / 2 ? 'out-of-stock' : 'low-stock';
                                        $statusText = $item['quantity_available'] <= $item['reorder_level'] / 2 ? 'Critical' : 'Low';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                            <td class="fw-bold text-danger"><?php echo $item['quantity_available']; ?></td>
                                            <td><?php echo $item['reorder_level']; ?></td>
                                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                            <td class="no-print">
                                                <a href="view-item.php?id=<?php echo $item['id']; ?>" class="action-btn view" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="restock.php?id=<?php echo $item['id']; ?>" class="action-btn edit" title="Restock">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Categories Overview -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="table-card">
                    <div class="table-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="table-title">
                            <i class="fas fa-tags text-primary"></i>
                            Categories Overview
                        </div>
                        <span class="badge-custom primary">All Categories</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Category Name</th>
                                    <th>Items</th>
                                    <th>Total Stock</th>
                                    <th>Total Value</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT c.id, c.name, 
                                           COUNT(DISTINCT o.id) as item_count,
                                           COALESCE(SUM(s.quantity_available), 0) as total_stock,
                                           COALESCE(SUM(s.quantity_available * o.unit_cost), 0) as total_value
                                    FROM categories c
                                    LEFT JOIN office_supplies o ON c.id = o.category_id
                                    LEFT JOIN stock s ON o.id = s.office_supply_id
                                    GROUP BY c.id, c.name
                                    ORDER BY total_value DESC
                                    LIMIT 5
                                ");
                                $stmt->execute();
                                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                $i = 1;
                                foreach ($categories as $cat) {
                                    ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td><?php echo $cat['item_count']; ?></td>
                                        <td><?php echo $cat['total_stock']; ?></td>
                                        <td>$<?php echo number_format($cat['total_value'], 2); ?></td>
                                        <td class="no-print">
                                            <a href="category-details.php?id=<?php echo $cat['id']; ?>" class="action-btn view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-category.php?id=<?php echo $cat['id']; ?>" class="action-btn edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                    $i++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Supplies Table -->
        <div class="row g-4" data-aos="fade-up" data-aos-delay="300">
            <div class="col-12">
                <div class="table-card">
                    <div class="table-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="table-title">
                            <i class="fas fa-box-open text-success"></i>
                            Office Supplies Inventory
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge-custom success">In Stock</span>
                            <span class="badge-custom warning">Low Stock</span>
                            <span class="badge-custom danger">Out of Stock</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Unit Cost</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT o.id, o.item_name, o.unit_cost, o.created_at,
                                           c.name as category_name,
                                           s.quantity_available, s.reorder_level, s.updated_at
                                    FROM office_supplies o
                                    JOIN categories c ON o.category_id = c.id
                                    LEFT JOIN stock s ON o.id = s.office_supply_id
                                    ORDER BY o.created_at DESC
                                    LIMIT 10
                                ");
                                $stmt->execute();
                                $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($supplies as $supply) {
                                    $qty = $supply['quantity_available'] ?? 0;
                                    $reorder = $supply['reorder_level'] ?? 10;
                                    
                                    if ($qty <= 0) {
                                        $statusClass = 'out-of-stock';
                                        $statusText = 'Out of Stock';
                                    } elseif ($qty <= $reorder) {
                                        $statusClass = 'low-stock';
                                        $statusText = 'Low Stock';
                                    } else {
                                        $statusClass = 'in-stock';
                                        $statusText = 'In Stock';
                                    }
                                    
                                    $lastUpdated = $supply['updated_at'] ? date('M d, Y', strtotime($supply['updated_at'])) : 'N/A';
                                    ?>
                                    <tr>
                                        <td>#<?php echo $supply['id']; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($supply['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($supply['category_name']); ?></td>
                                        <td>$<?php echo number_format($supply['unit_cost'], 2); ?></td>
                                        <td class="fw-bold"><?php echo $qty; ?></td>
                                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                        <td><?php echo $lastUpdated; ?></td>
                                        <td class="no-print">
                                            <a href="view-supply.php?id=<?php echo $supply['id']; ?>" class="action-btn view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-supply.php?id=<?php echo $supply['id']; ?>" class="action-btn edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete-supply.php?id=<?php echo $supply['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this item?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export and Print Buttons -->
        <div class="row mt-4 no-print">
            <div class="col-12">
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-primary" onclick="exportToCSV()">
                        <i class="fas fa-file-csv me-2"></i>Export to CSV
                    </button>
                    <button class="btn btn-outline-success" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 10
        });

        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Value Chart
            const valueCtx = document.getElementById('valueChart').getContext('2d');
            new Chart(valueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        data: [12000, 15000, 18000, 16000, 21000, <?php echo $totalValue; ?>],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    }
                }
            });

            // Stock Chart
            const stockCtx = document.getElementById('stockChart').getContext('2d');
            new Chart(stockCtx, {
                type: 'bar',
                data: {
                    labels: ['Office Supplies', 'Electronics', 'Furniture', 'Stationery'],
                    datasets: [{
                        data: [45, 30, 25, <?php echo $avgStock; ?>],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    }
                }
            });

            // Health Chart
            const healthCtx = document.getElementById('healthChart').getContext('2d');
            new Chart(healthCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Healthy Stock', 'Low Stock', 'Out of Stock'],
                    datasets: [{
                        data: [<?php echo $healthyStock; ?>, <?php echo $stats['low_stock']; ?>, <?php echo $stats['supplies'] - $healthyStock - $stats['low_stock']; ?>],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '70%'
                }
            });
        });

        // Search functionality
        document.getElementById('searchTable')?.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#inventoryTable tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        // Export to CSV
        function exportToCSV() {
            const table = document.getElementById('inventoryTable');
            const rows = table.querySelectorAll('tr');
            const csv = [];
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                const rowData = [];
                cells.forEach(cell => {
                    if (!cell.classList.contains('no-print')) {
                        rowData.push('"' + cell.textContent.trim() + '"');
                    }
                });
                csv.push(rowData.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'inventory_report_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
        }

        // Refresh data every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + R to refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                location.reload();
            }
            
            // Ctrl + P to print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Ctrl + E to export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportToCSV();
            }
        });

        // Tooltip initialization
        const tooltips = document.querySelectorAll('[title]');
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip-custom';
                tooltip.textContent = this.title;
                tooltip.style.position = 'absolute';
                tooltip.style.background = '#1e293b';
                tooltip.style.color = 'white';
                tooltip.style.padding = '5px 10px';
                tooltip.style.borderRadius = '5px';
                tooltip.style.fontSize = '0.85rem';
                tooltip.style.zIndex = '1000';
                tooltip.style.pointerEvents = 'none';
                
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                
                this.addEventListener('mouseleave', function() {
                    tooltip.remove();
                });
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            // Reinitialize AOS
            AOS.refresh();
        });

        // Lazy loading for images
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));

        // Prevent zoom on input focus for mobile
        if (/Mobi|Android/i.test(navigator.userAgent)) {
            document.querySelectorAll('input, select, textarea').forEach(element => {
                element.addEventListener('focus', function() {
                    document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                });
                
                element.addEventListener('blur', function() {
                    document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes');
                });
            });
        }
    </script>
</body>
</html>