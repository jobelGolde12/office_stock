<?php
require __DIR__ . '/../app/init.php';

if (!$Ouser->is_login()) {
    redirect("login.php");
}

$page = $_GET['page'] ?? 'inventory_masterlist';
$actual_link = $page;

$categories = [];
$error = null;

try {
    $stmt = $pdo->query("SELECT * FROM inventory_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Unable to load categories: ' . $e->getMessage();
}
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" id="mainContent">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <h1 class="m-0 text-dark">Inventory Masterlist</h1>
          <small class="text-muted">Complete master inventory list of all supplies and materials (RPCI)</small>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right mb-0">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Inventory Masterlist</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <style>
        :root {
          --inv-border: #e5eaf4;
          --inv-soft-bg: linear-gradient(145deg, #f8fbff 0%, #eef4ff 100%);
          --inv-ink: #0f172a;
          --inv-muted: #64748b;
          --inv-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .inv-hero {
          border: 1px solid var(--inv-border);
          border-radius: 14px;
          padding: 16px;
          margin-bottom: 14px;
          background: var(--inv-soft-bg);
          box-shadow: var(--inv-shadow);
        }

        .inv-hero-title {
          margin: 0;
          color: var(--inv-ink);
          font-weight: 700;
          font-size: 1.05rem;
        }

        .inv-hero-sub {
          color: var(--inv-muted);
          margin: 4px 0 0;
          font-size: 0.86rem;
        }

        .inv-toolbar {
          display: flex;
          flex-wrap: wrap;
          align-items: center;
          gap: 10px;
          margin-top: 12px;
        }

        .inv-search {
          position: relative;
          flex: 1 1 280px;
          min-width: 220px;
        }

        .inv-search i {
          position: absolute;
          left: 11px;
          top: 50%;
          transform: translateY(-50%);
          color: #94a3b8;
          font-size: 0.84rem;
        }

        .inv-search input {
          width: 100%;
          border: 1px solid #dbe4f2;
          border-radius: 9px;
          padding: 9px 12px 9px 32px;
          font-size: 0.86rem;
          color: var(--inv-ink);
          background: #fff;
          outline: none;
        }

        .inv-search input:focus {
          border-color: #93c5fd;
          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .inv-filter {
          min-width: 180px;
        }

        .inv-filter select {
          border: 1px solid #dbe4f2;
          border-radius: 9px;
          padding: 9px 12px;
          font-size: 0.86rem;
          color: var(--inv-ink);
          background: #fff;
          outline: none;
          cursor: pointer;
        }

        .inv-kpi {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          border-radius: 999px;
          padding: 7px 11px;
          border: 1px solid #cfe0ff;
          background: #eaf2ff;
          color: #1e3a8a;
          font-size: 0.78rem;
          font-weight: 700;
        }

        .inv-card {
          border: 1px solid var(--inv-border);
          border-radius: 14px;
          box-shadow: var(--inv-shadow);
        }

        .inv-card .card-header {
          background: #fff;
          border-bottom: 1px solid var(--inv-border);
          padding: 13px 16px;
        }

        .inv-card .card-title {
          margin: 0;
          color: var(--inv-ink);
          font-size: 0.95rem;
          font-weight: 700;
        }

        .inv-table-wrap {
          overflow-x: auto;
          border: 1px solid #e8edf6;
          border-radius: 11px;
        }

        .inv-table {
          width: 100%;
          border-collapse: collapse;
        }

        .inv-table thead th {
          background: #f8fbff;
          color: #475569;
          font-size: 0.8rem;
          font-weight: 700;
          padding: 12px 16px;
          text-align: left;
          border-bottom: 1px solid #e7eef9;
          white-space: nowrap;
        }

        .inv-table tbody td {
          font-size: 0.86rem;
          color: #0f172a;
          padding: 12px 16px;
          border-bottom: 1px solid #f1f5f9;
        }

        .inv-table tbody tr:hover {
          background: #f8fafc;
        }

        .badge-category {
          display: inline-flex;
          padding: 4px 10px;
          border-radius: 999px;
          font-size: 0.75rem;
          font-weight: 600;
          background: #eef2ff;
          color: #4338ca;
        }

        .badge-low {
          background: #fef3c7;
          color: #92400e;
        }

        .badge-out {
          background: #fee2e2;
          color: #991b1b;
        }

        .badge-ok {
          background: #dcfce7;
          color: #166534;
        }

        .empty-state {
          padding: 40px;
          text-align: center;
          color: #64748b;
        }

        .alert-error {
          background: #fef2f2;
          border: 1px solid #fecaca;
          color: #991b1b;
          padding: 12px 16px;
          border-radius: 8px;
          margin-bottom: 16px;
        }

        .text-right {
          text-align: right;
        }
      </style>

      <?php if ($error): ?>
        <div class="alert-error">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <div class="inv-hero">
        <h2 class="inv-hero-title">Inventory Masterlist (RPCI)</h2>
        <p class="inv-hero-sub">Central database of all office supplies stored in the PRI inventory system. Use the search and filter to find items.</p>
        <div class="inv-toolbar">
          <div class="inv-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input id="inventorySearch" type="search" placeholder="Search by item name..." aria-label="Search inventory">
          </div>
          <div class="inv-filter">
            <select id="categoryFilter">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars($cat['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <span class="inv-kpi" id="inventoryTotalCount">
            <i class="fas fa-boxes"></i> Loading items...
          </span>
        </div>
      </div>

      <div class="card inv-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Inventory Items</h3>
          <a href="index.php?page=import_csv" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-file-import mr-1"></i> Import CSV
          </a>
        </div>
        <div class="card-body p-0">
          <div class="inv-table-wrap">
            <table id="inventoryTable" class="inv-table display">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Item Name</th>
                  <th>Category</th>
                  <th>Unit</th>
                  <th class="text-right">Unit Value</th>
                  <th class="text-right">Qty in Stock</th>
                  <th class="text-right">Total Value</th>
                  <th>Status</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody id="inventoryTbody">
                <tr>
                  <td colspan="9" class="empty-state">Loading inventory data...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
(function() {
  const tbody = document.getElementById('inventoryTbody');
  const searchInput = document.getElementById('inventorySearch');
  const categoryFilter = document.getElementById('categoryFilter');
  const countChip = document.getElementById('inventoryTotalCount');
  
  let allItems = [];
  let filteredItems = [];

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatMoney(value) {
    return '₱' + Number(value || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function getStockBadge(qty, minLevel) {
    if (qty === 0) {
      return '<span class="badge-category badge-out">Out of Stock</span>';
    } else if (qty <= minLevel) {
      return '<span class="badge-category badge-low">Low Stock</span>';
    }
    return '<span class="badge-category badge-ok">In Stock</span>';
  }

  function renderTable(items) {
    if (!tbody) return;
    
    if (!items || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No inventory items found. Import CSV or add items manually.</td></tr>';
      return;
    }

    tbody.innerHTML = items.map(function(item) {
      const totalValue = (item.stock_quantity || 0) * (item.unit_price || 0);
      const minLevel = item.min_stock_level || 5;
      
      return '<tr>' +
        '<td>' + escapeHtml(item.id || '') + '</td>' +
        '<td><strong>' + escapeHtml(item.item_name || 'N/A') + '</strong></td>' +
        '<td>' + escapeHtml(item.category_name || 'Uncategorized') + '</td>' +
        '<td>' + escapeHtml(item.unit_measure || '-') + '</td>' +
        '<td class="text-right">' + formatMoney(item.unit_price) + '</td>' +
        '<td class="text-right">' + Number(item.stock_quantity || 0).toLocaleString() + '</td>' +
        '<td class="text-right">' + formatMoney(totalValue) + '</td>' +
        '<td>' + getStockBadge(item.stock_quantity, minLevel) + '</td>' +
        '<td>' + (item.updated_at ? new Date(item.updated_at).toLocaleDateString() : '-') + '</td>' +
      '</tr>';
    }).join('');
  }

  function updateCount() {
    if (countChip) {
      countChip.innerHTML = '<i class="fas fa-boxes"></i> ' + filteredItems.length.toLocaleString() + ' items';
    }
  }

  function filterItems() {
    const search = (searchInput?.value || '').toLowerCase();
    const category = categoryFilter?.value || '';

    filteredItems = allItems.filter(function(item) {
      const matchesSearch = !search || 
        (item.item_name || '').toLowerCase().includes(search) ||
        (item.category_name || '').toLowerCase().includes(search);
      
      const matchesCategory = !category || (item.category_name || '') === category;
      
      return matchesSearch && matchesCategory;
    });

    renderTable(filteredItems);
    updateCount();
  }

  async function loadInventory() {
    try {
      const response = await fetch('app/ajax/inventory_data.php', { credentials: 'same-origin' });
      const payload = await response.json();

      if (!response.ok || payload.status !== 'ok') {
        throw new Error(payload.message || 'Failed to load inventory');
      }

      allItems = Array.isArray(payload.data) ? payload.data : [];
      filteredItems = [...allItems];
      
      renderTable(filteredItems);
      updateCount();
    } catch (error) {
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-state">Error loading inventory: ' + escapeHtml(error.message) + '</td></tr>';
      }
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterItems);
  }
  
  if (categoryFilter) {
    categoryFilter.addEventListener('change', filterItems);
  }

  loadInventory();
})();
</script>
