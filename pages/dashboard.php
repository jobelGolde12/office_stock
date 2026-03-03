<?php
if (!$obj->is_login()) {
    redirect("login.php");
}

$analyticsError = null;
$stats = [
    'sales_month' => 0,
    'purchase_month' => 0,
    'due_sales' => 0,
    'due_purchase' => 0,
    'low_stock_count' => 0,
    'total_products' => 0,
    'recent_sale_count' => 0,
];

$recent_sales = [];
$low_stock_items = [];
$top_products = [];
$chart_data = [
    'months' => [],
    'sales' => [],
    'purchases' => [],
    'stock_status' => [0, 0, 0],
];

$dashboardInsights = [
    'net_cash_flow' => 0,
    'collection_rate' => 0,
    'inventory_health' => 100,
    'empty_state' => true,
];

$start_month = date('Y-m-01');
$end_month = date('Y-m-t');

try {
    $sql = "SELECT SUM(net_total) as total FROM invoice WHERE order_date >= :start_date AND order_date <= :end_date";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start_date', $start_month);
    $stmt->bindValue(':end_date', $end_month);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['sales_month'] = (float) ($row['total'] ?? 0);

    $sql = "SELECT SUM(purchase_net_total) as total FROM purchase_products WHERE purchase_date >= :start_date AND purchase_date <= :end_date";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start_date', $start_month);
    $stmt->bindValue(':end_date', $end_month);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['purchase_month'] = (float) ($row['total'] ?? 0);

    $sql = "SELECT SUM(due_amount) as total FROM invoice";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['due_sales'] = (float) ($row['total'] ?? 0);

    $sql = "SELECT SUM(purchase_due_bill) as total FROM purchase_products";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['due_purchase'] = (float) ($row['total'] ?? 0);

    $sql = "SELECT COUNT(*) as total FROM products WHERE quantity <= alert_quanttity";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['low_stock_count'] = (int) ($row['total'] ?? 0);

    $sql = "SELECT COUNT(*) as total FROM products";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_products'] = (int) ($row['total'] ?? 0);

    $sql = "SELECT * FROM invoice ORDER BY order_date DESC LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['recent_sale_count'] = count($recent_sales);

    $sql = "SELECT * FROM products WHERE quantity <= alert_quanttity ORDER BY quantity ASC LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT product_name, SUM(quantity) as qty FROM invoice_details GROUP BY product_name ORDER BY qty DESC LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    for ($i = 5; $i >= 0; $i--) {
        $monthKey = date('Y-m', strtotime("-$i months"));
        $chart_data['months'][] = date('M Y', strtotime("-$i months"));
        $chart_data['sales'][$monthKey] = 0;
        $chart_data['purchases'][$monthKey] = 0;
    }

    $sql = "SELECT strftime('%Y-%m', order_date) as month, SUM(net_total) as total FROM invoice WHERE order_date >= date('now', '-6 months') GROUP BY month";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (isset($chart_data['sales'][$r['month']])) {
            $chart_data['sales'][$r['month']] = (float) $r['total'];
        }
    }

    $sql = "SELECT strftime('%Y-%m', purchase_date) as month, SUM(purchase_net_total) as total FROM purchase_products WHERE purchase_date >= date('now', '-6 months') GROUP BY month";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (isset($chart_data['purchases'][$r['month']])) {
            $chart_data['purchases'][$r['month']] = (float) $r['total'];
        }
    }

    $sql = "SELECT 
            SUM(CASE WHEN quantity > alert_quanttity THEN 1 ELSE 0 END) as healthy,
            SUM(CASE WHEN quantity <= alert_quanttity AND quantity > 0 THEN 1 ELSE 0 END) as low,
            SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
            FROM products";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stock_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $chart_data['stock_status'] = [
        (int) ($stock_row['healthy'] ?? 0),
        (int) ($stock_row['low'] ?? 0),
        (int) ($stock_row['out_of_stock'] ?? 0),
    ];
} catch (Exception $e) {
    $analyticsError = "Error loading dashboard data: " . $e->getMessage();
}

$dashboardInsights['net_cash_flow'] = $stats['sales_month'] - $stats['purchase_month'];
$dashboardInsights['collection_rate'] = $stats['sales_month'] > 0
    ? (($stats['sales_month'] - $stats['due_sales']) / $stats['sales_month']) * 100
    : 0;
$dashboardInsights['inventory_health'] = $stats['total_products'] > 0
    ? (($stats['total_products'] - $stats['low_stock_count']) / $stats['total_products']) * 100
    : 100;
$dashboardInsights['empty_state'] = empty($recent_sales) && empty($top_products) && $stats['total_products'] === 0;
?>

<style>
    :root {
        --dash-primary: #1d4ed8;
        --dash-primary-soft: #e8f0ff;
        --dash-success: #15803d;
        --dash-danger: #be123c;
        --dash-warning: #c2410c;
        --dash-surface: #ffffff;
        --dash-text: #0f172a;
        --dash-muted: #64748b;
        --dash-border: #e2e8f0;
        --dash-radius: 8px;
    }

    .dashboard-shell {
        padding: 24px;
    }

    .dash-panel {
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        border-radius: var(--dash-radius);
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    .dash-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
    }

    .dash-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dash-text);
    }

    .dash-subtitle {
        margin: 4px 0 0;
        color: var(--dash-muted);
        font-size: 0.92rem;
    }

    .search-toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border: 1px solid var(--dash-border);
        background: #fff;
        border-radius: 8px;
        min-width: 360px;
        max-width: 520px;
        width: 100%;
    }

    .search-toolbar:focus-within {
        border-color: var(--dash-primary);
        box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.14);
    }

    .search-icon {
        color: #94a3b8;
        margin-left: 4px;
    }

    .search-toolbar input {
        flex: 1;
        border: 0;
        outline: 0;
        height: 36px;
        color: var(--dash-text);
        font-size: 0.92rem;
        background: transparent;
    }

    .search-toolbar button {
        border: 1px solid var(--dash-border);
        height: 34px;
        border-radius: 6px;
        padding: 0 12px;
        background: #f8fafc;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .search-toolbar button:hover,
    .search-toolbar button:focus {
        background: #eef2f7;
        outline: none;
    }

    .kpi-card {
        min-height: 108px;
        padding: 16px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .kpi-icon.primary { background: #e8f0ff; color: #1d4ed8; }
    .kpi-icon.success { background: #e8f9ef; color: #15803d; }
    .kpi-icon.warning { background: #fff3e8; color: #c2410c; }
    .kpi-icon.danger { background: #ffe6ee; color: #be123c; }

    .kpi-label {
        color: var(--dash-muted);
        font-size: 0.82rem;
        margin-bottom: 4px;
    }

    .kpi-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--dash-text);
        line-height: 1.2;
    }

    .insight-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .insight-box {
        border: 1px solid var(--dash-border);
        border-radius: 8px;
        padding: 16px;
        background: linear-gradient(180deg, #fff, #f8fbff);
    }

    .insight-box .label {
        color: var(--dash-muted);
        font-size: 0.8rem;
        margin-bottom: 6px;
    }

    .insight-box .value {
        font-size: 1.18rem;
        font-weight: 700;
        color: var(--dash-text);
    }

    .section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 16px 16px 0;
    }

    .section-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--dash-text);
    }

    .section-actions .btn {
        font-size: 0.82rem;
    }

    .table-wrap {
        padding: 12px 16px 16px;
        overflow-x: auto;
    }

    .dash-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 680px;
    }

    .dash-table th,
    .dash-table td {
        padding: 12px;
        border-bottom: 1px solid #e8edf5;
        text-align: left;
        font-size: 0.88rem;
    }

    .dash-table th {
        color: #475569;
        font-weight: 600;
        background: #f8fbff;
    }

    .dash-table tbody tr:nth-child(even) {
        background: #fcfdff;
    }

    .dash-table tbody tr:hover {
        background: #f3f8ff;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-paid { background: #dcfce7; color: #166534; }
    .status-partial { background: #ffedd5; color: #9a3412; }

    .list-panel {
        padding: 10px 16px 16px;
    }

    .list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #eef2f7;
        font-size: 0.88rem;
    }

    .list-row:last-child {
        border-bottom: 0;
    }

    .list-row .meta {
        color: var(--dash-muted);
        font-size: 0.78rem;
        margin-top: 2px;
    }

    .metric-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        padding: 4px 10px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .empty-state {
        padding: 18px;
        text-align: center;
        color: #64748b;
        font-size: 0.88rem;
        border: 1px dashed #dbe3ef;
        border-radius: 8px;
        background: #fbfdff;
    }

    .skeleton {
        position: relative;
        overflow: hidden;
        background: #edf2f7;
        border-radius: 8px;
    }

    .skeleton::after {
        content: '';
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.75), transparent);
        animation: dashShimmer 1.2s infinite;
    }

    @keyframes dashShimmer {
        100% { transform: translateX(100%); }
    }

    .charts-loading {
        padding: 16px;
        display: grid;
        gap: 12px;
    }

    .charts-loading .skeleton {
        height: 220px;
    }

    .supplies-card-body {
        padding: 12px 16px 16px;
    }

    .supplies-skeleton {
        display: grid;
        gap: 10px;
    }

    .supplies-skeleton .skeleton {
        height: 44px;
        border-radius: 8px;
    }

    .supplies-table-wrap {
        overflow-x: auto;
        border: 1px solid #e8edf5;
        border-radius: 8px;
    }

    .supplies-table-wrap .dash-table {
        min-width: 1080px;
    }

    .supplies-cost {
        font-weight: 700;
        color: #0f172a;
    }

    .hidden-until-ready {
        display: block;
    }

    .only-loading {
        display: none;
    }

    .focus-outline:focus {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }

    @media (max-width: 1199.98px) {
        .dashboard-shell {
            padding: 16px;
        }

        .search-toolbar {
            min-width: 100%;
        }
    }

    @media (max-width: 991.98px) {
        .dash-header {
            flex-direction: column;
            align-items: stretch;
        }

        .insight-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-shell" id="dashboardShell">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Dashboard</h1>
            <p class="dash-subtitle">Operational analytics, inventory health, and sales activity overview.</p>
        </div>
        <div class="search-toolbar" role="search" aria-label="Dashboard search">
            <i class="fas fa-search search-icon" aria-hidden="true"></i>
            <input
                id="dashboardSearch"
                type="search"
                placeholder="Search invoices, customers, products..."
                aria-label="Search dashboard data"
                class="focus-outline"
            >
            <button id="dashboardFilterBtn" type="button" aria-label="Toggle quick filter" class="focus-outline">
                <i class="fas fa-sliders-h mr-1" aria-hidden="true"></i> Filters
            </button>
        </div>
    </div>

    <?php if ($analyticsError): ?>
        <div class="alert alert-danger mb-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i>
            <?php echo htmlspecialchars($analyticsError); ?>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <section class="dash-panel" aria-labelledby="officeSuppliesHeading">
                <div class="section-head">
                    <h2 id="officeSuppliesHeading" class="section-title">Office Supplies</h2>
                    <div class="section-actions">
                        <span class="metric-chip" id="officeSuppliesCount">Loading...</span>
                    </div>
                </div>
                <div class="supplies-card-body">
                    <div id="officeSuppliesLoading" class="supplies-skeleton" aria-hidden="true">
                        <div class="skeleton"></div>
                        <div class="skeleton"></div>
                        <div class="skeleton"></div>
                        <div class="skeleton"></div>
                    </div>

                    <div id="officeSuppliesContainer" class="supplies-table-wrap" style="display: none;">
                        <table class="dash-table" id="officeSuppliesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category ID</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Unit Cost</th>
                                    <th>Stocks</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody id="officeSuppliesTbody"></tbody>
                        </table>
                    </div>

                    <div id="officeSuppliesEmpty" class="empty-state mt-2" style="display: none;">
                        No office supplies found.
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row" style="row-gap: 16px;">
        <div class="col-12 col-sm-6 col-lg-3">
            <article class="dash-panel kpi-card" aria-label="Sales this month">
                <span class="kpi-icon primary"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                <div>
                    <div class="kpi-label">Sales (This Month)</div>
                    <div class="kpi-value">$<?php echo number_format($stats['sales_month'], 2); ?></div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <article class="dash-panel kpi-card" aria-label="Purchases this month">
                <span class="kpi-icon success"><i class="fas fa-truck-loading" aria-hidden="true"></i></span>
                <div>
                    <div class="kpi-label">Purchases (This Month)</div>
                    <div class="kpi-value">$<?php echo number_format($stats['purchase_month'], 2); ?></div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <article class="dash-panel kpi-card" aria-label="Low stock items">
                <span class="kpi-icon danger"><i class="fas fa-box-open" aria-hidden="true"></i></span>
                <div>
                    <div class="kpi-label">Low Stock Items</div>
                    <div class="kpi-value"><?php echo number_format($stats['low_stock_count']); ?> / <?php echo number_format($stats['total_products']); ?></div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <article class="dash-panel kpi-card" aria-label="Outstanding sales due">
                <span class="kpi-icon warning"><i class="fas fa-receipt" aria-hidden="true"></i></span>
                <div>
                    <div class="kpi-label">Sales Due</div>
                    <div class="kpi-value">$<?php echo number_format($stats['due_sales'], 2); ?></div>
                </div>
            </article>
        </div>
    </div>

    <section class="dash-panel mt-3 p-3" aria-labelledby="insightHeading">
        <div class="d-flex justify-content-between align-items-center mb-3" style="gap: 8px;">
            <h2 id="insightHeading" class="section-title">Analytics Snapshot</h2>
            <span class="metric-chip"><?php echo date('M d, Y'); ?></span>
        </div>
        <div class="insight-grid">
            <div class="insight-box">
                <div class="label">Net Cash Flow</div>
                <div class="value">$<?php echo number_format($dashboardInsights['net_cash_flow'], 2); ?></div>
            </div>
            <div class="insight-box">
                <div class="label">Collection Rate</div>
                <div class="value"><?php echo number_format($dashboardInsights['collection_rate'], 1); ?>%</div>
            </div>
            <div class="insight-box">
                <div class="label">Inventory Health</div>
                <div class="value"><?php echo number_format($dashboardInsights['inventory_health'], 1); ?>%</div>
            </div>
        </div>
        <?php if ($dashboardInsights['empty_state']): ?>
            <div class="empty-state mt-3">
                <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
                No operational records yet. Add products, purchases, or sales to populate analytics.
            </div>
        <?php endif; ?>
    </section>

    <div class="row mt-3" style="row-gap: 16px;">
        <div class="col-lg-8">
            <section class="dash-panel" aria-labelledby="trendHeading">
                <div class="section-head">
                    <h2 id="trendHeading" class="section-title">Sales vs Purchase Trend</h2>
                </div>
                <div class="only-loading charts-loading" aria-hidden="true">
                    <div class="skeleton"></div>
                </div>
                <div class="hidden-until-ready p-3">
                    <canvas id="trendChart" style="height: 240px;"></canvas>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="dash-panel" aria-labelledby="stockHeading">
                <div class="section-head">
                    <h2 id="stockHeading" class="section-title">Stock Distribution</h2>
                </div>
                <div class="only-loading charts-loading" aria-hidden="true">
                    <div class="skeleton"></div>
                </div>
                <div class="hidden-until-ready p-3">
                    <canvas id="stockChart" style="height: 240px;"></canvas>
                </div>
            </section>
        </div>
    </div>

    <div class="row mt-3" style="row-gap: 16px;">
        <div class="col-lg-8">
            <section class="dash-panel" aria-labelledby="recentHeading">
                <div class="section-head">
                    <h2 id="recentHeading" class="section-title">Recent Sales</h2>
                    <div class="section-actions">
                        <a href="index.php?page=sell_list" class="btn btn-sm btn-outline-primary focus-outline">View All</a>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="dash-table" id="recentSalesTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_sales)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">No recent sales available.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <tr class="search-row" data-search="<?php echo htmlspecialchars(strtolower(($sale['invoice_number'] ?? '') . ' ' . ($sale['customer_name'] ?? ''))); ?>">
                                        <td><?php echo htmlspecialchars($sale['invoice_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo !empty($sale['order_date']) ? date('M d, Y', strtotime($sale['order_date'])) : 'N/A'; ?></td>
                                        <td>$<?php echo number_format((float) ($sale['net_total'] ?? 0), 2); ?></td>
                                        <td>
                                            <?php if (((float) ($sale['due_amount'] ?? 0)) > 0): ?>
                                                <span class="status-badge status-partial">Partial</span>
                                            <?php else: ?>
                                                <span class="status-badge status-paid">Paid</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="dash-panel" aria-labelledby="topProductHeading">
                <div class="section-head">
                    <h2 id="topProductHeading" class="section-title">Top Products</h2>
                </div>
                <div class="list-panel" id="topProductsList">
                    <?php if (empty($top_products)): ?>
                        <div class="empty-state">No product sales data yet.</div>
                    <?php else: ?>
                        <?php foreach ($top_products as $index => $prod): ?>
                            <div class="list-row search-row" data-search="<?php echo htmlspecialchars(strtolower($prod['product_name'] ?? '')); ?>">
                                <div>
                                    <div><strong>#<?php echo $index + 1; ?></strong> <?php echo htmlspecialchars($prod['product_name'] ?? 'N/A'); ?></div>
                                    <div class="meta">Top performing item</div>
                                </div>
                                <span class="metric-chip"><?php echo (int) ($prod['qty'] ?? 0); ?> sold</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="dash-panel mt-3" aria-labelledby="lowStockHeading">
                <div class="section-head">
                    <h2 id="lowStockHeading" class="section-title">Low Stock Alerts</h2>
                </div>
                <div class="list-panel" id="lowStockList">
                    <?php if (empty($low_stock_items)): ?>
                        <div class="empty-state">All stock levels are currently healthy.</div>
                    <?php else: ?>
                        <?php foreach ($low_stock_items as $item): ?>
                            <div class="list-row search-row" data-search="<?php echo htmlspecialchars(strtolower($item['product_name'] ?? '')); ?>">
                                <div>
                                    <div><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></div>
                                    <div class="meta">Alert qty: <?php echo (int) ($item['alert_quanttity'] ?? 0); ?></div>
                                </div>
                                <span class="status-badge status-partial"><?php echo (int) ($item['quantity'] ?? 0); ?> left</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

</div>

<script>
(function () {
    function debounce(fn, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function normalize(value) {
        return (value || '').toString().toLowerCase().trim();
    }

    function filterSearchables(term) {
        const rows = document.querySelectorAll('.search-row');
        let visible = 0;
        rows.forEach((row) => {
            const text = normalize(row.getAttribute('data-search'));
            const show = !term || text.includes(term);
            row.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });

        const tableBody = document.querySelector('#recentSalesTable tbody');
        if (tableBody) {
            const existingEmpty = tableBody.querySelector('.dynamic-empty');
            if (existingEmpty) existingEmpty.remove();
            const tableRows = tableBody.querySelectorAll('tr.search-row');
            const shownRows = Array.from(tableRows).filter((r) => r.style.display !== 'none').length;
            if (tableRows.length > 0 && shownRows === 0) {
                const tr = document.createElement('tr');
                tr.className = 'dynamic-empty';
                tr.innerHTML = '<td colspan="5"><div class="empty-state">No matches found for your search.</div></td>';
                tableBody.appendChild(tr);
            }
        }

        return visible;
    }

    const searchInput = document.getElementById('dashboardSearch');
    const filterBtn = document.getElementById('dashboardFilterBtn');

    if (searchInput) {
        const onSearch = debounce((event) => {
            filterSearchables(normalize(event.target.value));
        }, 280);
        searchInput.addEventListener('input', onSearch);
    }

    if (filterBtn) {
        filterBtn.addEventListener('click', function () {
            const onlyLowStock = this.getAttribute('data-low-only') === '1';
            this.setAttribute('data-low-only', onlyLowStock ? '0' : '1');
            this.innerHTML = onlyLowStock
                ? '<i class="fas fa-sliders-h mr-1" aria-hidden="true"></i> Filters'
                : '<i class="fas fa-filter mr-1" aria-hidden="true"></i> Low Stock';

            const lowStockList = document.getElementById('lowStockList');
            const topProductsList = document.getElementById('topProductsList');
            if (lowStockList && topProductsList) {
                if (onlyLowStock) {
                    topProductsList.style.opacity = '1';
                    lowStockList.style.opacity = '1';
                } else {
                    topProductsList.style.opacity = '0.35';
                    lowStockList.style.opacity = '1';
                }
            }
        });
    }

    const trendCtx = document.getElementById('trendChart');
    const stockCtx = document.getElementById('stockChart');
    const suppliesLoading = document.getElementById('officeSuppliesLoading');
    const suppliesContainer = document.getElementById('officeSuppliesContainer');
    const suppliesEmpty = document.getElementById('officeSuppliesEmpty');
    const suppliesTbody = document.getElementById('officeSuppliesTbody');
    const suppliesCount = document.getElementById('officeSuppliesCount');

    if (trendCtx && stockCtx && typeof Chart !== 'undefined') {
        new Chart(trendCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_data['months']); ?>,
                datasets: [
                    {
                        label: 'Sales',
                        borderColor: '#1d4ed8',
                        backgroundColor: 'rgba(29, 78, 216, 0.08)',
                        data: <?php echo json_encode(array_values($chart_data['sales'])); ?>,
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                    },
                    {
                        label: 'Purchases',
                        borderColor: '#15803d',
                        backgroundColor: 'rgba(21, 128, 61, 0.06)',
                        data: <?php echo json_encode(array_values($chart_data['purchases'])); ?>,
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.18)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        new Chart(stockCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: <?php echo json_encode($chart_data['stock_status']); ?>,
                    backgroundColor: ['#15803d', '#f59e0b', '#e11d48'],
                    borderWidth: 0,
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    function formatDate(value) {
        if (!value) return 'N/A';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return String(value);
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderOfficeSupplies(rows) {
        suppliesTbody.innerHTML = '';
        rows.forEach((item) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(item.id ?? '')}</td>
                <td>${escapeHtml(item.category_id ?? '')}</td>
                <td>${escapeHtml(item.item_name ?? 'N/A')}</td>
                <td>${escapeHtml(item.category ?? 'Uncategorized')}</td>
                <td>${escapeHtml(item.description ?? '-')}</td>
                <td class=\"supplies-cost\">$${Number(item.unit_cost || 0).toFixed(2)}</td>
                <td>${escapeHtml(item.stocks ?? 0)}</td>
                <td>${escapeHtml(formatDate(item.created_at))}</td>
                <td>${escapeHtml(formatDate(item.updated_at))}</td>
            `;
            suppliesTbody.appendChild(tr);
        });
    }

    async function loadOfficeSupplies() {
        if (!suppliesLoading || !suppliesContainer || !suppliesEmpty || !suppliesTbody || !suppliesCount) {
            return;
        }

        suppliesLoading.style.display = 'grid';
        suppliesContainer.style.display = 'none';
        suppliesEmpty.style.display = 'none';
        suppliesCount.textContent = 'Loading...';

        try {
            const response = await fetch('app/ajax/office_supplies_data.php', { credentials: 'same-origin' });
            const payload = await response.json();

            if (!response.ok || payload.status !== 'ok') {
                throw new Error(payload.message || 'Failed to load office supplies');
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];
            suppliesCount.textContent = `${rows.length} items`;

            if (rows.length === 0) {
                suppliesEmpty.style.display = 'block';
            } else {
                renderOfficeSupplies(rows);
                suppliesContainer.style.display = 'block';
            }
        } catch (error) {
            suppliesCount.textContent = 'Unavailable';
            suppliesEmpty.style.display = 'block';
            suppliesEmpty.textContent = `Unable to load office supplies: ${error.message}`;
        } finally {
            suppliesLoading.style.display = 'none';
        }
    }

    loadOfficeSupplies();

})();
</script>
