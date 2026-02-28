<?php
$analyticsError = null;

$metrics = [
    'total_categories' => 0,
    'total_supplies' => 0,
    'total_units' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
    'healthy_stock' => 0,
    'total_value' => 0.0,
    'avg_unit_cost' => 0.0,
];

$topCategories = [];
$lowStockItems = [];
$recentSupplies = [];
$monthlyMap = [];

if (!function_exists('dashboard_fetch_one')) {
    function dashboard_fetch_one($pdo, $sql, $params = [], $key = null, $default = 0)
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $default;
        }

        if ($key !== null) {
            return array_key_exists($key, $row) && $row[$key] !== null ? $row[$key] : $default;
        }

        return reset($row) !== false ? reset($row) : $default;
    }
}

if (!function_exists('dashboard_fetch_all')) {
    function dashboard_fetch_all($pdo, $sql, $params = [])
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

try {
    $metrics['total_categories'] = (int) dashboard_fetch_one($pdo, 'SELECT COUNT(*) AS total FROM categories', [], 'total', 0);
    $metrics['total_supplies'] = (int) dashboard_fetch_one($pdo, 'SELECT COUNT(*) AS total FROM office_supplies', [], 'total', 0);
    $metrics['total_units'] = (int) dashboard_fetch_one($pdo, 'SELECT COALESCE(SUM(quantity_available), 0) AS total FROM stock', [], 'total', 0);
    $metrics['low_stock'] = (int) dashboard_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS total FROM stock WHERE quantity_available > 0 AND quantity_available <= reorder_level',
        [],
        'total',
        0
    );
    $metrics['out_of_stock'] = (int) dashboard_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS total FROM stock WHERE quantity_available <= 0',
        [],
        'total',
        0
    );
    $metrics['healthy_stock'] = (int) dashboard_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS total FROM stock WHERE quantity_available > reorder_level',
        [],
        'total',
        0
    );
    $metrics['total_value'] = (float) dashboard_fetch_one(
        $pdo,
        'SELECT COALESCE(SUM(s.quantity_available * o.unit_cost), 0) AS total_value
         FROM stock s
         INNER JOIN office_supplies o ON o.id = s.office_supply_id',
        [],
        'total_value',
        0
    );
    $metrics['avg_unit_cost'] = (float) dashboard_fetch_one(
        $pdo,
        'SELECT COALESCE(AVG(unit_cost), 0) AS avg_cost FROM office_supplies',
        [],
        'avg_cost',
        0
    );

    $topCategories = dashboard_fetch_all(
        $pdo,
        'SELECT
            c.name AS category_name,
            COUNT(DISTINCT o.id) AS item_count,
            COALESCE(SUM(s.quantity_available), 0) AS total_units,
            COALESCE(SUM(s.quantity_available * o.unit_cost), 0) AS total_value
         FROM categories c
         LEFT JOIN office_supplies o ON o.category_id = c.id
         LEFT JOIN stock s ON s.office_supply_id = o.id
         GROUP BY c.id, c.name
         ORDER BY total_units DESC, c.name ASC
         LIMIT 6'
    );

    $lowStockItems = dashboard_fetch_all(
        $pdo,
        'SELECT
            o.id AS office_supply_id,
            o.item_name,
            c.name AS category_name,
            COALESCE(s.quantity_available, 0) AS quantity_available,
            COALESCE(s.reorder_level, 0) AS reorder_level
         FROM office_supplies o
         INNER JOIN categories c ON c.id = o.category_id
         LEFT JOIN stock s ON s.office_supply_id = o.id
         WHERE COALESCE(s.quantity_available, 0) <= COALESCE(s.reorder_level, 0)
         ORDER BY quantity_available ASC, o.item_name ASC
         LIMIT 7'
    );

    $recentSupplies = dashboard_fetch_all(
        $pdo,
        'SELECT
            o.id,
            o.item_name,
            c.name AS category_name,
            COALESCE(s.quantity_available, 0) AS quantity_available,
            COALESCE(s.reorder_level, 0) AS reorder_level,
            COALESCE(s.last_updated, o.updated_at, o.created_at) AS last_change
         FROM office_supplies o
         INNER JOIN categories c ON c.id = o.category_id
         LEFT JOIN stock s ON s.office_supply_id = o.id
         ORDER BY last_change DESC
         LIMIT 10'
    );

    $monthlyRows = dashboard_fetch_all(
        $pdo,
        "SELECT strftime('%Y-%m', created_at) AS month_key, COUNT(*) AS total_added
         FROM office_supplies
         WHERE created_at >= datetime('now', '-5 months', 'start of month')
         GROUP BY month_key
         ORDER BY month_key ASC"
    );

    foreach ($monthlyRows as $row) {
        $monthlyMap[$row['month_key']] = (int) $row['total_added'];
    }
} catch (Exception $e) {
    $analyticsError = $e->getMessage();
}

$monthLabels = [];
$monthValues = [];

$monthCursor = new DateTime('first day of this month');
$monthCursor->modify('-5 months');

for ($i = 0; $i < 6; $i++) {
    $key = $monthCursor->format('Y-m');
    $monthLabels[] = $monthCursor->format('M Y');
    $monthValues[] = isset($monthlyMap[$key]) ? (int) $monthlyMap[$key] : 0;
    $monthCursor->modify('+1 month');
}

$categoryLabels = [];
$categoryValues = [];
foreach ($topCategories as $category) {
    $categoryLabels[] = $category['category_name'];
    $categoryValues[] = (int) $category['total_units'];
}
?>

<style>
.layout-navbar-fixed .wrapper .content-wrapper.dashboard-page {
  padding-top: 1rem;
}
.dashboard-analytics .metric-card {
  border-top: 3px solid transparent;
  transition: box-shadow .2s ease, transform .2s ease;
}
.dashboard-analytics .metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, .08);
}
.dashboard-analytics .metric-primary { border-top-color: #007bff; }
.dashboard-analytics .metric-success { border-top-color: #28a745; }
.dashboard-analytics .metric-warning { border-top-color: #ffc107; }
.dashboard-analytics .metric-danger { border-top-color: #dc3545; }
.dashboard-analytics .metric-value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.1;
}
.dashboard-analytics .chart-wrap {
  position: relative;
  height: 300px;
}
.dashboard-analytics .status-pill {
  display: inline-block;
  min-width: 84px;
  text-align: center;
  padding: .25rem .55rem;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 600;
}
.dashboard-analytics .status-good { background-color: #d4edda; color: #155724; }
.dashboard-analytics .status-low { background-color: #fff3cd; color: #856404; }
.dashboard-analytics .status-out { background-color: #f8d7da; color: #721c24; }
@media (max-width: 991.98px) {
  .layout-navbar-fixed .wrapper .content-wrapper.dashboard-page {
    padding-top: 1.25rem;
  }
  .dashboard-analytics .chart-wrap {
    height: 260px;
  }
}
@media (max-width: 575.98px) {
  .dashboard-analytics .metric-value {
    font-size: 1.4rem;
  }
  .dashboard-analytics .chart-wrap {
    height: 220px;
  }
}
</style>

<div class="content-wrapper dashboard-page">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Dashboard Analytics</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Analytics</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content dashboard-analytics">
    <div class="container-fluid">
      <?php if ($analyticsError !== null): ?>
        <div class="alert alert-danger">
          Unable to load analytics data from Turso. <?php echo htmlspecialchars($analyticsError); ?>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 mb-3">
          <div class="card metric-card metric-primary h-100">
            <div class="card-body">
              <p class="text-muted mb-1">Total Categories</p>
              <div class="metric-value"><?php echo number_format($metrics['total_categories']); ?></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 mb-3">
          <div class="card metric-card metric-success h-100">
            <div class="card-body">
              <p class="text-muted mb-1">Supply Items</p>
              <div class="metric-value"><?php echo number_format($metrics['total_supplies']); ?></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 mb-3">
          <div class="card metric-card metric-warning h-100">
            <div class="card-body">
              <p class="text-muted mb-1">Units in Stock</p>
              <div class="metric-value"><?php echo number_format($metrics['total_units']); ?></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 mb-3">
          <div class="card metric-card metric-danger h-100">
            <div class="card-body">
              <p class="text-muted mb-1">Low or Out of Stock</p>
              <div class="metric-value"><?php echo number_format($metrics['low_stock'] + $metrics['out_of_stock']); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-8 mb-3">
          <div class="card card-outline card-primary h-100">
            <div class="card-header border-0">
              <h3 class="card-title">Supplies Added (Last 6 Months)</h3>
            </div>
            <div class="card-body">
              <div class="chart-wrap">
                <canvas id="salesChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 mb-3">
          <div class="card card-outline card-success h-100">
            <div class="card-header border-0">
              <h3 class="card-title">Stock Status Mix</h3>
            </div>
            <div class="card-body">
              <div class="chart-wrap">
                <canvas id="pieChart"></canvas>
              </div>
              <hr>
              <div class="small text-muted mb-1">Inventory Value: <strong>$<?php echo number_format($metrics['total_value'], 2); ?></strong></div>
              <div class="small text-muted">Avg Unit Cost: <strong>$<?php echo number_format($metrics['avg_unit_cost'], 2); ?></strong></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6 mb-3">
          <div class="card card-outline card-info h-100">
            <div class="card-header border-0">
              <h3 class="card-title">Top Categories by Units</h3>
            </div>
            <div class="card-body">
              <div class="chart-wrap">
                <canvas id="categoryUnitsChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 mb-3">
          <div class="card card-outline card-warning h-100">
            <div class="card-header border-0">
              <h3 class="card-title">Low Stock Watchlist</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover text-nowrap mb-0">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Reorder</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($lowStockItems)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No low-stock items found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($lowStockItems as $item): ?>
                      <?php
                        $qty = (int) $item['quantity_available'];
                        $reorder = (int) $item['reorder_level'];
                        $pillClass = 'status-good';
                        $statusText = 'Healthy';
                        if ($qty <= 0) {
                            $pillClass = 'status-out';
                            $statusText = 'Out';
                        } elseif ($qty <= $reorder) {
                            $pillClass = 'status-low';
                            $statusText = 'Low';
                        }
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo $qty; ?></td>
                        <td><?php echo $reorder; ?></td>
                        <td><span class="status-pill <?php echo $pillClass; ?>"><?php echo $statusText; ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card card-outline card-secondary">
            <div class="card-header border-0">
              <h3 class="card-title">Latest Inventory Updates</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-striped table-hover text-nowrap mb-0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Reorder</th>
                    <th>Status</th>
                    <th>Updated</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentSupplies)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($recentSupplies as $row): ?>
                      <?php
                        $qty = (int) $row['quantity_available'];
                        $reorder = (int) $row['reorder_level'];
                        $pillClass = 'status-good';
                        $statusText = 'Healthy';
                        if ($qty <= 0) {
                            $pillClass = 'status-out';
                            $statusText = 'Out';
                        } elseif ($qty <= $reorder) {
                            $pillClass = 'status-low';
                            $statusText = 'Low';
                        }
                        $updatedAt = !empty($row['last_change']) ? date('M d, Y H:i', strtotime($row['last_change'])) : 'N/A';
                      ?>
                      <tr>
                        <td>#<?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo $qty; ?></td>
                        <td><?php echo $reorder; ?></td>
                        <td><span class="status-pill <?php echo $pillClass; ?>"><?php echo $statusText; ?></span></td>
                        <td><?php echo htmlspecialchars($updatedAt); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') {
    return;
  }

  var palette = {
    primary: '#007bff',
    success: '#28a745',
    warning: '#ffc107',
    danger: '#dc3545',
    slate: '#6c757d'
  };

  var monthLabels = <?php echo json_encode($monthLabels); ?>;
  var monthValues = <?php echo json_encode($monthValues); ?>;
  var categoryLabels = <?php echo json_encode($categoryLabels); ?>;
  var categoryValues = <?php echo json_encode($categoryValues); ?>;

  var salesCtx = document.getElementById('salesChart');
  if (salesCtx) {
    new Chart(salesCtx, {
      type: 'line',
      data: {
        labels: monthLabels,
        datasets: [{
          label: 'Supplies Added',
          data: monthValues,
          borderColor: palette.primary,
          backgroundColor: 'rgba(0, 123, 255, 0.12)',
          borderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: true,
          lineTension: 0.35
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 900,
          easing: 'easeOutQuart'
        },
        legend: {
          display: true,
          position: 'bottom'
        },
        tooltips: {
          enabled: true,
          mode: 'index',
          intersect: false
        },
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero: true,
              precision: 0
            },
            gridLines: {
              color: 'rgba(0,0,0,0.06)'
            }
          }],
          xAxes: [{
            gridLines: {
              display: false
            }
          }]
        }
      }
    });
  }

  var pieCtx = document.getElementById('pieChart');
  if (pieCtx) {
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: ['Healthy', 'Low Stock', 'Out of Stock'],
        datasets: [{
          data: [
            <?php echo (int) $metrics['healthy_stock']; ?>,
            <?php echo (int) $metrics['low_stock']; ?>,
            <?php echo (int) $metrics['out_of_stock']; ?>
          ],
          backgroundColor: [palette.success, palette.warning, palette.danger],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutoutPercentage: 65,
        animation: {
          duration: 1000,
          easing: 'easeOutQuart'
        },
        legend: {
          display: true,
          position: 'bottom'
        },
        tooltips: {
          enabled: true
        }
      }
    });
  }

  var categoryCtx = document.getElementById('categoryUnitsChart');
  if (categoryCtx) {
    new Chart(categoryCtx, {
      type: 'bar',
      data: {
        labels: categoryLabels,
        datasets: [{
          label: 'Units in Stock',
          data: categoryValues,
          backgroundColor: 'rgba(40, 167, 69, 0.75)',
          borderColor: palette.success,
          borderWidth: 1,
          maxBarThickness: 36
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 900,
          easing: 'easeOutQuart'
        },
        legend: {
          display: true,
          position: 'bottom'
        },
        tooltips: {
          enabled: true
        },
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero: true,
              precision: 0
            },
            gridLines: {
              color: 'rgba(0,0,0,0.06)'
            }
          }],
          xAxes: [{
            gridLines: {
              display: false
            }
          }]
        }
      }
    });
  }
});
</script>
