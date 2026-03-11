<?php
if (!$Ouser->is_login()) {
    redirect("login.php");
}

$analyticsError = null;

$kpi = [
    'sales_month' => 0.0,
    'purchase_month' => 0.0,
    'expense_month' => 0.0,
    'profit_month' => 0.0,
    'sales_due_total' => 0.0,
    'purchase_due_total' => 0.0,
    'collection_rate_month' => 0.0,
    'inventory_health' => 100.0,
    'low_stock_count' => 0,
    'total_products' => 0,
];

$chart = [
    'months' => [],
    'sales' => [],
    'purchases' => [],
    'expenses' => [],
    'stock_status' => [0, 0, 0],
    'payment_labels' => [],
    'payment_values' => [],
    'top_product_labels' => [],
    'top_product_values' => [],
    'category_labels' => [],
    'category_values' => [],
];

$recentSales = [];
$lowStockItems = [];

$officeSuppliesSummary = [
    'total_items' => 0,
    'total_stock_units' => 0,
    'estimated_stock_value' => 0.0,
];

$monthKeys = [];
for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("first day of -{$i} month");
    $monthKey = $date->format('Y-m');
    $monthKeys[] = $monthKey;
    $chart['months'][] = $date->format('M Y');
    $chart['sales'][$monthKey] = 0.0;
    $chart['purchases'][$monthKey] = 0.0;
    $chart['expenses'][$monthKey] = 0.0;
}

$focusMonthLabel = date('M Y');

try {
    $queryScalar = function ($sql, $params = [], $field = 'total') use ($pdo) {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($row[$field] ?? 0);
    };

    $fillMonthlySeries = function ($sql, &$targetArray, $params = []) use ($pdo) {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $month = (string) ($row['month_key'] ?? '');
            if ($month !== '' && array_key_exists($month, $targetArray)) {
                $targetArray[$month] = (float) ($row['total'] ?? 0);
            }
        }
    };

    // Use latest activity month so analytics remain visible for historical datasets.
    $anchorDate = new DateTime('now');
    $stmt = $pdo->prepare(
        "SELECT MAX(dt) AS latest_date FROM (
            SELECT MAX(order_date) AS dt FROM invoice
            UNION ALL
            SELECT MAX(purchase_date) AS dt FROM purchase_products
            UNION ALL
            SELECT MAX(ex_date) AS dt FROM expense
        ) src"
    );
    $stmt->execute();
    $latestDateRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $latestDate = (string) ($latestDateRow['latest_date'] ?? '');
    if ($latestDate !== '' && $latestDate !== '0000-00-00') {
        $parsedLatest = DateTime::createFromFormat('Y-m-d', $latestDate);
        if ($parsedLatest instanceof DateTime) {
            $anchorDate = $parsedLatest;
        }
    }

    $monthKeys = [];
    $chart['months'] = [];
    $chart['sales'] = [];
    $chart['purchases'] = [];
    $chart['expenses'] = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthDate = (clone $anchorDate)->modify("first day of -{$i} month");
        $monthKey = $monthDate->format('Y-m');
        $monthKeys[] = $monthKey;
        $chart['months'][] = $monthDate->format('M Y');
        $chart['sales'][$monthKey] = 0.0;
        $chart['purchases'][$monthKey] = 0.0;
        $chart['expenses'][$monthKey] = 0.0;
    }

    $rangeStart = (clone $anchorDate)->modify('first day of -5 month')->format('Y-m-01');
    $rangeEnd = (clone $anchorDate)->modify('last day of this month')->format('Y-m-d');
    $currentMonthStart = (clone $anchorDate)->modify('first day of this month')->format('Y-m-01');
    $currentMonthEnd = (clone $anchorDate)->modify('last day of this month')->format('Y-m-d');
    $currentMonthKey = (clone $anchorDate)->modify('first day of this month')->format('Y-m');
    $focusMonthLabel = (clone $anchorDate)->format('M Y');

    $fillMonthlySeries(
        "SELECT strftime('%Y-%m', order_date) AS month_key, SUM(net_total) AS total
         FROM invoice
         WHERE order_date >= :range_start AND order_date <= :range_end
         GROUP BY month_key",
        $chart['sales'],
        [':range_start' => $rangeStart, ':range_end' => $rangeEnd]
    );

    $fillMonthlySeries(
        "SELECT strftime('%Y-%m', purchase_date) AS month_key, SUM(purchase_net_total) AS total
         FROM purchase_products
         WHERE purchase_date >= :range_start AND purchase_date <= :range_end
         GROUP BY month_key",
        $chart['purchases'],
        [':range_start' => $rangeStart, ':range_end' => $rangeEnd]
    );

    $fillMonthlySeries(
        "SELECT strftime('%Y-%m', ex_date) AS month_key, SUM(amount) AS total
         FROM expense
         WHERE ex_date >= :range_start AND ex_date <= :range_end
         GROUP BY month_key",
        $chart['expenses'],
        [':range_start' => $rangeStart, ':range_end' => $rangeEnd]
    );

    $kpi['sales_month'] = (float) ($chart['sales'][$currentMonthKey] ?? 0);
    $kpi['purchase_month'] = (float) ($chart['purchases'][$currentMonthKey] ?? 0);
    $kpi['expense_month'] = (float) ($chart['expenses'][$currentMonthKey] ?? 0);
    $kpi['profit_month'] = $kpi['sales_month'] - $kpi['purchase_month'] - $kpi['expense_month'];

    $salesPaidMonth = $queryScalar(
        "SELECT SUM(paid_amount) AS total
         FROM invoice
         WHERE order_date >= :start_date AND order_date <= :end_date",
        [':start_date' => $currentMonthStart, ':end_date' => $currentMonthEnd]
    );
    $kpi['collection_rate_month'] = $kpi['sales_month'] > 0
        ? min(100, max(0, ($salesPaidMonth / $kpi['sales_month']) * 100))
        : 0;

    $kpi['sales_due_total'] = $queryScalar("SELECT SUM(due_amount) AS total FROM invoice");
    $kpi['purchase_due_total'] = $queryScalar("SELECT SUM(purchase_due_bill) AS total FROM purchase_products");

    $kpi['low_stock_count'] = (int) $queryScalar("SELECT COUNT(*) AS total FROM products WHERE quantity <= alert_quanttity");
    $kpi['total_products'] = (int) $queryScalar("SELECT COUNT(*) AS total FROM products");
    $kpi['inventory_health'] = $kpi['total_products'] > 0
        ? (($kpi['total_products'] - $kpi['low_stock_count']) / $kpi['total_products']) * 100
        : 100;

    $stmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN quantity > alert_quanttity THEN 1 ELSE 0 END) AS healthy,
            SUM(CASE WHEN quantity <= alert_quanttity AND quantity > 0 THEN 1 ELSE 0 END) AS low,
            SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock
         FROM products"
    );
    $stmt->execute();
    $stockRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $chart['stock_status'] = [
        (int) ($stockRow['healthy'] ?? 0),
        (int) ($stockRow['low'] ?? 0),
        (int) ($stockRow['out_of_stock'] ?? 0),
    ];

    $stmt = $pdo->prepare(
        "SELECT invoice_number, customer_name, order_date, net_total, due_amount, payment_type
         FROM invoice
         ORDER BY order_date DESC, id DESC
         LIMIT 8"
    );
    $stmt->execute();
    $recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT product_name, quantity, alert_quanttity
         FROM products
         WHERE quantity <= alert_quanttity
         ORDER BY quantity ASC, id DESC
         LIMIT 8"
    );
    $stmt->execute();
    $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT product_name, SUM(quantity) AS qty
         FROM invoice_details
         GROUP BY product_name
         ORDER BY qty DESC
         LIMIT 7"
    );
    $stmt->execute();
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($topProducts as $item) {
        $chart['top_product_labels'][] = (string) ($item['product_name'] ?? 'Unknown');
        $chart['top_product_values'][] = (int) ($item['qty'] ?? 0);
    }

    $stmt = $pdo->prepare(
        "SELECT COALESCE(payment_type, 'Unknown') AS label, SUM(net_total) AS total
         FROM invoice
         WHERE order_date >= :start_date AND order_date <= :end_date
         GROUP BY payment_type
         ORDER BY total DESC
         LIMIT 6"
    );
    $stmt->bindValue(':start_date', $currentMonthStart);
    $stmt->bindValue(':end_date', $currentMonthEnd);
    $stmt->execute();
    $paymentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($paymentRows as $row) {
        $chart['payment_labels'][] = (string) ($row['label'] ?? 'Unknown');
        $chart['payment_values'][] = (float) ($row['total'] ?? 0);
    }

    $stmt = $pdo->prepare(
        "SELECT COALESCE(p.catagory_name, 'Uncategorized') AS category_name, SUM(d.quantity) AS total
         FROM invoice_details d
         LEFT JOIN products p ON p.id = d.pid
         GROUP BY p.catagory_name
         ORDER BY total DESC
         LIMIT 6"
    );
    $stmt->execute();
    $categoryRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($categoryRows as $row) {
        $chart['category_labels'][] = (string) ($row['category_name'] ?? 'Uncategorized');
        $chart['category_values'][] = (int) ($row['total'] ?? 0);
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) AS total_items,
                COALESCE(SUM(COALESCE(s.quantity_available, 0)), 0) AS total_stock_units,
                COALESCE(SUM(COALESCE(s.quantity_available, 0) * COALESCE(os.unit_cost, 0)), 0) AS estimated_stock_value
             FROM office_supplies os
             LEFT JOIN stock s ON s.office_supply_id = os.id"
        );
        $stmt->execute();
        $summaryRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $officeSuppliesSummary['total_items'] = (int) ($summaryRow['total_items'] ?? 0);
        $officeSuppliesSummary['total_stock_units'] = (int) ($summaryRow['total_stock_units'] ?? 0);
        $officeSuppliesSummary['estimated_stock_value'] = (float) ($summaryRow['estimated_stock_value'] ?? 0);
    } catch (Exception $ignored) {
        // Office supplies tables may not exist in every tenant DB.
    }

    // PRI Inventory Analytics
    $priInventory = [
        'total_items' => 0,
        'total_stock_units' => 0,
        'total_inventory_value' => 0.0,
        'low_stock_count' => 0,
        'out_of_stock_count' => 0,
        'top_consuming_division' => null,
        'top_consuming_value' => 0.0,
        'monthly_issuance_value' => 0.0,
    ];

    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_items,
                COALESCE(SUM(stock_quantity), 0) as total_stock_units,
                COALESCE(SUM(stock_quantity * unit_price), 0) as total_value,
                SUM(CASE WHEN stock_quantity <= min_stock_level AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
            FROM inventory_master
        ");
        $invRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $priInventory['total_items'] = (int) ($invRow['total_items'] ?? 0);
        $priInventory['total_stock_units'] = (int) ($invRow['total_stock_units'] ?? 0);
        $priInventory['total_inventory_value'] = (float) ($invRow['total_value'] ?? 0);
        $priInventory['low_stock_count'] = (int) ($invRow['low_stock'] ?? 0);
        $priInventory['out_of_stock_count'] = (int) ($invRow['out_of_stock'] ?? 0);

        $currentMonth = date('Y-m');
        $stmt = $pdo->prepare("
            SELECT d.code, d.name, SUM(i.total_value) as total_value
            FROM issuances i
            JOIN divisions d ON i.division_id = d.id
            WHERE strftime('%Y-%m', i.issuance_date) = ?
            GROUP BY d.id
            ORDER BY total_value DESC
            LIMIT 1
        ");
        $stmt->execute([$currentMonth]);
        $topDiv = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($topDiv) {
            $priInventory['top_consuming_division'] = $topDiv['code'] ?? 'N/A';
            $priInventory['top_consuming_value'] = (float) ($topDiv['total_value'] ?? 0);
        }

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_value), 0) as total
            FROM issuances
            WHERE strftime('%Y-%m', issuance_date) = ?
        ");
        $stmt->execute([$currentMonth]);
        $issuanceRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $priInventory['monthly_issuance_value'] = (float) ($issuanceRow['total'] ?? 0);

    } catch (Exception $ignored) {
    }
} catch (Exception $e) {
    $analyticsError = 'Unable to load analytics right now. ' . $e->getMessage();
}

$netTrend = [];
foreach ($monthKeys as $key) {
    $netTrend[] = ((float) ($chart['sales'][$key] ?? 0))
        - ((float) ($chart['purchases'][$key] ?? 0))
        - ((float) ($chart['expenses'][$key] ?? 0));
}

$isAnalyticsEmpty = array_sum(array_map('abs', array_values($chart['sales']))) === 0
    && array_sum(array_map('abs', array_values($chart['purchases']))) === 0
    && array_sum(array_map('abs', array_values($chart['expenses']))) === 0
    && $kpi['total_products'] === 0;
?>

<style>
    :root {
        --dash-bg: linear-gradient(155deg, #f8fbff 0%, #eef4ff 45%, #f7fbf7 100%);
        --dash-surface: #ffffff;
        --dash-text: #111827;
        --dash-muted: #64748b;
        --dash-border: #e5edf8;
        --dash-primary: #2563eb;
        --dash-primary-soft: #dbeafe;
        --dash-success: #16a34a;
        --dash-warning: #ea580c;
        --dash-danger: #dc2626;
        --dash-violet: #4338ca;
        --dash-shadow: 0 16px 30px rgba(15, 23, 42, 0.06);
        --dash-radius: 14px;
    }

    .dashboard-shell {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        padding: 24px;
        background: var(--dash-bg);
        border-radius: 16px;
        overflow-x: clip;
    }

    .dashboard-shell .row {
        margin-left: 0;
        margin-right: 0;
    }

    .dashboard-shell .row > [class*="col-"] {
        padding-left: 6px;
        padding-right: 6px;
    }

    .hero-band {
        background: linear-gradient(125deg, #0f172a 0%, #1e3a8a 52%, #0f766e 100%);
        border-radius: 18px;
        padding: 22px;
        color: #f8fafc;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
        margin-bottom: 18px;
    }

    .hero-band,
    .hero-band * {
        color: #ffffff !important;
    }

    .hero-title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 700;
        letter-spacing: 0.2px;
    }

    .hero-subtitle {
        margin: 6px 0 0;
        color: #ffffff;
        font-size: 0.92rem;
    }

    .hero-stats {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .hero-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #f8fafc;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .dashboard-shell .card {
        border: 1px solid var(--dash-border);
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
        overflow: hidden;
    }

    .metric-card {
        background: var(--dash-surface);
        min-height: 114px;
    }

    .metric-card .card-body {
        padding: 16px;
    }

    .metric-label {
        color: var(--dash-muted);
        font-size: 0.8rem;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 600;
    }

    .metric-value {
        margin: 0;
        color: var(--dash-text);
        font-size: 1.2rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .metric-note {
        margin-top: 8px;
        font-size: 0.78rem;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-analytics {
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid transparent;
    }

    .badge-primary-soft {
        background: #e0eaff;
        color: #1e40af;
        border-color: #c6d9ff;
    }

    .badge-success-soft {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }

    .badge-warning-soft {
        background: #ffedd5;
        color: #9a3412;
        border-color: #fed7aa;
    }

    .chart-card .card-header,
    .data-card .card-header {
        background: #fff;
        border-bottom: 1px solid var(--dash-border);
        padding: 12px 16px;
    }

    .chart-title {
        margin: 0;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .chart-subtitle {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 0.78rem;
    }

    .chart-card .card-body {
        padding: 14px 16px 12px;
    }

    .chart-wrap {
        position: relative;
        min-height: 280px;
    }

    .chart-wrap.compact {
        min-height: 240px;
    }

    .analytics-table {
        width: 100%;
        border-collapse: collapse;
    }

    .analytics-table th,
    .analytics-table td {
        padding: 11px;
        border-bottom: 1px solid #edf2f8;
        font-size: 0.86rem;
        vertical-align: middle;
    }

    .analytics-table th {
        color: #475569;
        font-weight: 600;
        background: #f8fbff;
    }

    .analytics-table tbody tr:hover {
        background: #f7faff;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 3px 9px;
        font-size: 0.74rem;
        font-weight: 600;
    }

    .status-paid {
        background: #dcfce7;
        color: #166534;
    }

    .status-partial {
        background: #ffedd5;
        color: #9a3412;
    }

    .list-panel {
        padding: 10px 16px 14px;
    }

    .list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid #edf2f8;
    }

    .list-row:last-child {
        border-bottom: 0;
    }

    .list-name {
        color: #0f172a;
        font-size: 0.87rem;
        font-weight: 600;
    }

    .list-meta {
        color: #64748b;
        font-size: 0.77rem;
        margin-top: 3px;
    }

    .list-chip {
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        padding: 4px 9px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .supplies-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .supplies-summary .box {
        border: 1px solid #e8eef8;
        border-radius: 10px;
        padding: 10px;
        background: #fafcff;
    }

    .supplies-summary .box .label {
        color: #64748b;
        font-size: 0.76rem;
        margin-bottom: 2px;
    }

    .supplies-summary .box .value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
    }

    .supplies-table-wrap {
        overflow-x: auto;
        border: 1px solid #e8eef8;
        border-radius: 10px;
    }

    .supplies-table-wrap .analytics-table {
        min-width: 1020px;
    }

    .supplies-loading {
        display: grid;
        gap: 8px;
        padding: 10px 0;
    }

    .skeleton {
        height: 42px;
        border-radius: 8px;
        background: linear-gradient(95deg, #e8eef8 8%, #f8fbff 42%, #e8eef8 72%);
        background-size: 200% 100%;
        animation: pulseX 1.15s linear infinite;
    }

    @keyframes pulseX {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    .empty-state {
        padding: 14px;
        border: 1px dashed #cbd9ee;
        border-radius: 10px;
        background: #fbfdff;
        color: #64748b;
        font-size: 0.86rem;
        text-align: center;
    }

    @media (max-width: 1199.98px) {
        .dashboard-shell {
            padding: 16px;
        }

        .chart-wrap {
            min-height: 260px;
        }
    }

    @media (max-width: 991.98px) {
        .hero-title {
            font-size: 1.22rem;
        }

        .supplies-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content-wrapper" id="mainContent">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
<div class="dashboard-shell">
    <section class="hero-band" aria-labelledby="analyticsHeading">
        <h1 id="analyticsHeading" class="hero-title">PRI Supplies Inventory Dashboard</h1>
        <p class="hero-subtitle">Real-time overview of inventory status, supply consumption, and stock value.</p>
        <div class="hero-stats">
            <span class="hero-chip"><i class="far fa-calendar-alt"></i> <?php echo date('F Y'); ?></span>
            <span class="hero-chip"><i class="fas fa-boxes"></i> <?php echo number_format($priInventory['total_items']); ?> items tracked</span>
            <span class="hero-chip"><i class="fas fa-exclamation-triangle"></i> <?php echo number_format($priInventory['low_stock_count']); ?> low stock alerts</span>
        </div>
    </section>

    <?php if ($analyticsError): ?>
        <div class="alert alert-danger mb-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <?php echo htmlspecialchars($analyticsError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="row" style="row-gap: 12px;">
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Total Inventory Value</div>
                    <p class="metric-value">₱<?php echo number_format($priInventory['total_inventory_value'], 2); ?></p>
                    <span class="metric-note"><span class="badge-analytics badge-primary-soft">Current Stock Worth</span></span>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Total Stock Units</div>
                    <p class="metric-value"><?php echo number_format($priInventory['total_stock_units']); ?></p>
                    <span class="metric-note"><span class="badge-analytics badge-primary-soft">All Items</span></span>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Monthly Issuance (<?php echo date('M Y'); ?>)</div>
                    <p class="metric-value">₱<?php echo number_format($priInventory['monthly_issuance_value'], 2); ?></p>
                    <span class="metric-note"><span class="badge-analytics badge-warning-soft">RSMI Total</span></span>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Top Consuming Division</div>
                    <p class="metric-value"><?php echo htmlspecialchars($priInventory['top_consuming_division'] ?? 'N/A'); ?></p>
                    <span class="metric-note">
                        <span class="badge-analytics <?php echo $priInventory['top_consuming_value'] > 0 ? 'badge-success-soft' : 'badge-warning-soft'; ?>">
                            ₱<?php echo number_format($priInventory['top_consuming_value'], 2); ?>
                        </span>
                    </span>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Low Stock Alerts</div>
                    <p class="metric-value"><?php echo number_format($priInventory['low_stock_count']); ?></p>
                    <span class="metric-note">Items below minimum level</span>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Out of Stock</div>
                    <p class="metric-value"><?php echo number_format($priInventory['out_of_stock_count']); ?></p>
                    <span class="metric-note">Items with zero quantity</span>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Total Items</div>
                    <p class="metric-value"><?php echo number_format($priInventory['total_items']); ?></p>
                    <span class="metric-note">In inventory masterlist</span>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card metric-card">
                <div class="card-body">
                    <div class="metric-label">Total Divisions</div>
                    <p class="metric-value">7</p>
                    <span class="metric-note">Cost centers configured</span>
                </div>
            </article>
        </div>
    </div>

    <?php if ($priInventory['total_items'] === 0): ?>
        <div class="empty-state mt-3">
            No inventory data available yet. Go to <a href="index.php?page=import_csv">Import CSV</a> to load inventory from RPCI file.
        </div>
    <?php endif; ?>

    <div class="row mt-3" style="row-gap: 12px;">
        <div class="col-lg-8">
            <section class="card chart-card" aria-labelledby="trendAnalyticsHeading">
                <div class="card-header d-flex justify-content-between align-items-center" style="gap: 8px;">
                    <div>
                        <h2 id="trendAnalyticsHeading" class="chart-title">6-Month Revenue, Purchase, and Expense Trend</h2>
                        <p class="chart-subtitle">Compare business inflow and outflow month by month.</p>
                    </div>
                    <span class="badge-analytics badge-primary-soft">Last 6 Months</span>
                </div>
                <div class="card-body">
                    <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card chart-card" aria-labelledby="stockDistributionHeading">
                <div class="card-header">
                    <h2 id="stockDistributionHeading" class="chart-title">Stock Distribution</h2>
                    <p class="chart-subtitle">Healthy, low, and out-of-stock products.</p>
                </div>
                <div class="card-body">
                    <div class="chart-wrap compact"><canvas id="stockChart"></canvas></div>
                </div>
            </section>
        </div>
    </div>

    <div class="row mt-3" style="row-gap: 12px;">
        <div class="col-lg-6">
            <section class="card chart-card" aria-labelledby="productRankHeading">
                <div class="card-header">
                    <h2 id="productRankHeading" class="chart-title">Top Products by Units Sold</h2>
                    <p class="chart-subtitle">From invoice line-item quantity.</p>
                </div>
                <div class="card-body">
                    <div class="chart-wrap compact"><canvas id="topProductsChart"></canvas></div>
                </div>
            </section>
        </div>
        <div class="col-lg-3">
            <section class="card chart-card" aria-labelledby="paymentMixHeading">
                <div class="card-header">
                    <h2 id="paymentMixHeading" class="chart-title">Payment Mix</h2>
                    <p class="chart-subtitle">Current month sales split.</p>
                </div>
                <div class="card-body">
                    <div class="chart-wrap compact"><canvas id="paymentChart"></canvas></div>
                </div>
            </section>
        </div>
        <div class="col-lg-3">
            <section class="card chart-card" aria-labelledby="categoryMixHeading">
                <div class="card-header">
                    <h2 id="categoryMixHeading" class="chart-title">Category Demand</h2>
                    <p class="chart-subtitle">Units sold by category.</p>
                </div>
                <div class="card-body">
                    <div class="chart-wrap compact"><canvas id="categoryChart"></canvas></div>
                </div>
            </section>
        </div>
    </div>

    <div class="row mt-3" style="row-gap: 12px;">
        <div class="col-lg-8">
            <section class="card data-card" aria-labelledby="recentSalesHeading">
                <div class="card-header d-flex justify-content-between align-items-center" style="gap: 8px;">
                    <h2 id="recentSalesHeading" class="chart-title">Recent Sales</h2>
                    <a href="index.php?page=sell_list" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0" style="overflow-x: auto;">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Payment Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentSales)): ?>
                                <tr>
                                    <td colspan="6"><div class="empty-state m-2">No recent sales available.</div></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['invoice_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo !empty($sale['order_date']) ? date('M d, Y', strtotime($sale['order_date'])) : 'N/A'; ?></td>
                                        <td>$<?php echo number_format((float) ($sale['net_total'] ?? 0), 2); ?></td>
                                        <td><?php echo htmlspecialchars($sale['payment_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php if ((float) ($sale['due_amount'] ?? 0) > 0): ?>
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
            <section class="card data-card" aria-labelledby="lowStockHeading">
                <div class="card-header">
                    <h2 id="lowStockHeading" class="chart-title">Low Stock Alerts</h2>
                </div>
                <div class="list-panel">
                    <?php if (empty($lowStockItems)): ?>
                        <div class="empty-state">All products are above alert quantity.</div>
                    <?php else: ?>
                        <?php foreach ($lowStockItems as $item): ?>
                            <div class="list-row">
                                <div>
                                    <div class="list-name"><?php echo htmlspecialchars($item['product_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="list-meta">Alert: <?php echo (int) ($item['alert_quanttity'] ?? 0); ?></div>
                                </div>
                                <span class="list-chip"><?php echo (int) ($item['quantity'] ?? 0); ?> left</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <section class="card data-card" aria-labelledby="officeSuppliesHeading">
                <div class="card-header d-flex justify-content-between align-items-center" style="gap: 10px;">
                    <h2 id="officeSuppliesHeading" class="chart-title">Office Supplies Snapshot</h2>
                    <span class="badge-analytics badge-primary-soft" id="officeSuppliesCount">
                        <?php echo number_format($officeSuppliesSummary['total_items']); ?> items
                    </span>
                </div>
                <div class="card-body">
                    <div class="supplies-summary">
                        <div class="box">
                            <div class="label">Total Items</div>
                            <div class="value" id="officeSuppliesTotalItems"><?php echo number_format($officeSuppliesSummary['total_items']); ?></div>
                        </div>
                        <div class="box">
                            <div class="label">Stock Units</div>
                            <div class="value" id="officeSuppliesStockUnits"><?php echo number_format($officeSuppliesSummary['total_stock_units']); ?></div>
                        </div>
                        <div class="box">
                            <div class="label">Estimated Stock Value</div>
                            <div class="value" id="officeSuppliesStockValue">$<?php echo number_format($officeSuppliesSummary['estimated_stock_value'], 2); ?></div>
                        </div>
                    </div>

                    <div id="officeSuppliesLoading" class="supplies-loading mt-3" aria-hidden="true" style="display:none;">
                        <div class="skeleton"></div>
                        <div class="skeleton"></div>
                        <div class="skeleton"></div>
                    </div>

                    <div id="officeSuppliesContainer" class="supplies-table-wrap mt-3" style="display: none;">
                        <table class="analytics-table" id="officeSuppliesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Item Name</th>
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

                    <div id="officeSuppliesEmpty" class="empty-state mt-3" style="display:none;">
                        No office supplies found.
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
        </div>
    </section>
</div>

<script>
(function () {
    function money(value) {
        return '$' + Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    const monthLabels = <?php echo json_encode($chart['months']); ?>;
    const salesSeries = <?php echo json_encode(array_values($chart['sales'])); ?>;
    const purchaseSeries = <?php echo json_encode(array_values($chart['purchases'])); ?>;
    const expenseSeries = <?php echo json_encode(array_values($chart['expenses'])); ?>;
    const netSeries = <?php echo json_encode($netTrend); ?>;

    const stockSeries = <?php echo json_encode($chart['stock_status']); ?>;
    const paymentLabels = <?php echo json_encode($chart['payment_labels']); ?>;
    const paymentValues = <?php echo json_encode($chart['payment_values']); ?>;
    const topProductLabels = <?php echo json_encode($chart['top_product_labels']); ?>;
    const topProductValues = <?php echo json_encode($chart['top_product_values']); ?>;
    const categoryLabels = <?php echo json_encode($chart['category_labels']); ?>;
    const categoryValues = <?php echo json_encode($chart['category_values']); ?>;

    const chartFont = "'Inter', 'Segoe UI', sans-serif";

    function getGridColor(alpha) {
        return 'rgba(100, 116, 139, ' + alpha + ')';
    }

    function createGradient(ctx, from, to) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, from);
        gradient.addColorStop(1, to);
        return gradient;
    }

    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#334155';
        Chart.defaults.font.family = chartFont;

        const trendEl = document.getElementById('trendChart');
        if (trendEl) {
            const tctx = trendEl.getContext('2d');
            const salesFill = createGradient(tctx, 'rgba(37, 99, 235, 0.32)', 'rgba(37, 99, 235, 0.02)');

            new Chart(tctx, {
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Sales',
                            data: salesSeries,
                            borderColor: '#2563eb',
                            backgroundColor: salesFill,
                            fill: true,
                            tension: 0.36,
                            borderWidth: 2.4,
                            pointRadius: 2.8,
                            pointHoverRadius: 4
                        },
                        {
                            type: 'line',
                            label: 'Purchases',
                            data: purchaseSeries,
                            borderColor: '#0f766e',
                            backgroundColor: 'rgba(15, 118, 110, 0.08)',
                            fill: false,
                            tension: 0.33,
                            borderDash: [6, 4],
                            borderWidth: 2.1,
                            pointRadius: 2.5
                        },
                        {
                            type: 'bar',
                            label: 'Expenses',
                            data: expenseSeries,
                            backgroundColor: 'rgba(234, 88, 12, 0.35)',
                            borderColor: 'rgba(234, 88, 12, 0.8)',
                            borderWidth: 1,
                            borderRadius: 8,
                            maxBarThickness: 24
                        },
                        {
                            type: 'line',
                            label: 'Net',
                            data: netSeries,
                            borderColor: '#4338ca',
                            backgroundColor: 'rgba(67, 56, 202, 0.08)',
                            fill: false,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 3
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 14
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + money(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: getGridColor(0.2)
                            },
                            ticks: {
                                callback: function (value) {
                                    return '$' + Number(value).toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        const stockEl = document.getElementById('stockChart');
        if (stockEl) {
            new Chart(stockEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Healthy', 'Low Stock', 'Out of Stock'],
                    datasets: [{
                        data: stockSeries,
                        backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '66%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 11,
                                padding: 11
                            }
                        }
                    }
                }
            });
        }

        const topProductsEl = document.getElementById('topProductsChart');
        if (topProductsEl) {
            new Chart(topProductsEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: topProductLabels,
                    datasets: [{
                        label: 'Units Sold',
                        data: topProductValues,
                        backgroundColor: '#4f46e5cc',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: getGridColor(0.14) }
                        },
                        y: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        const paymentEl = document.getElementById('paymentChart');
        if (paymentEl) {
            new Chart(paymentEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: paymentLabels.length ? paymentLabels : ['No Data'],
                    datasets: [{
                        data: paymentValues.length ? paymentValues : [1],
                        backgroundColor: paymentValues.length
                            ? ['#2563eb', '#0f766e', '#f59e0b', '#7c3aed', '#e11d48', '#0891b2']
                            : ['#cbd5e1'],
                        borderWidth: 0
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, padding: 8 }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    if (!paymentValues.length) return 'No Data';
                                    return ctx.label + ': ' + money(ctx.parsed);
                                }
                            }
                        }
                    }
                }
            });
        }

        const categoryEl = document.getElementById('categoryChart');
        if (categoryEl) {
            new Chart(categoryEl.getContext('2d'), {
                type: 'polarArea',
                data: {
                    labels: categoryLabels.length ? categoryLabels : ['No Data'],
                    datasets: [{
                        data: categoryValues.length ? categoryValues : [1],
                        backgroundColor: categoryValues.length
                            ? ['#2563eb99', '#0f766e99', '#f59e0b99', '#7c3aed99', '#e11d4899', '#0891b299']
                            : ['#cbd5e1'],
                        borderWidth: 1,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            grid: { color: getGridColor(0.2) },
                            angleLines: { color: getGridColor(0.2) }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, padding: 8 }
                        }
                    }
                }
            });
        }
    }

    const suppliesLoading = document.getElementById('officeSuppliesLoading');
    const suppliesContainer = document.getElementById('officeSuppliesContainer');
    const suppliesEmpty = document.getElementById('officeSuppliesEmpty');
    const suppliesTbody = document.getElementById('officeSuppliesTbody');
    const suppliesCount = document.getElementById('officeSuppliesCount');
    const suppliesItemsEl = document.getElementById('officeSuppliesTotalItems');
    const suppliesUnitsEl = document.getElementById('officeSuppliesStockUnits');
    const suppliesValueEl = document.getElementById('officeSuppliesStockValue');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) return 'N/A';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return String(value);
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
    }

    function renderOfficeSupplies(rows) {
        suppliesTbody.innerHTML = '';
        rows.forEach(function (item) {
            const stock = Number(item.stocks || 0);
            const cost = Number(item.unit_cost || 0);
            const tr = document.createElement('tr');
            tr.innerHTML = ''
                + '<td>' + escapeHtml(item.id ?? '') + '</td>'
                + '<td>' + escapeHtml(item.category ?? 'Uncategorized') + '</td>'
                + '<td>' + escapeHtml(item.item_name ?? 'N/A') + '</td>'
                + '<td>' + escapeHtml(item.description ?? '-') + '</td>'
                + '<td>' + money(cost) + '</td>'
                + '<td>' + escapeHtml(stock) + '</td>'
                + '<td>' + escapeHtml(formatDate(item.created_at)) + '</td>'
                + '<td>' + escapeHtml(formatDate(item.updated_at)) + '</td>';
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

        try {
            const response = await fetch('app/ajax/office_supplies_data.php', { credentials: 'same-origin' });
            const payload = await response.json();

            if (!response.ok || payload.status !== 'ok') {
                throw new Error(payload.message || 'Failed to load office supplies');
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];
            let totalUnits = 0;
            let totalValue = 0;
            rows.forEach(function (item) {
                const stock = Number(item.stocks || 0);
                const cost = Number(item.unit_cost || 0);
                totalUnits += stock;
                totalValue += stock * cost;
            });

            suppliesCount.textContent = rows.length + ' items';
            if (suppliesItemsEl) suppliesItemsEl.textContent = Number(rows.length).toLocaleString();
            if (suppliesUnitsEl) suppliesUnitsEl.textContent = Number(totalUnits).toLocaleString();
            if (suppliesValueEl) suppliesValueEl.textContent = money(totalValue);

            if (!rows.length) {
                suppliesEmpty.style.display = 'block';
            } else {
                renderOfficeSupplies(rows);
                suppliesContainer.style.display = 'block';
            }
        } catch (error) {
            suppliesCount.textContent = 'Unavailable';
            suppliesEmpty.style.display = 'block';
            suppliesEmpty.textContent = 'Unable to load office supplies: ' + error.message;
        } finally {
            suppliesLoading.style.display = 'none';
        }
    }

    loadOfficeSupplies();
})();
</script>
