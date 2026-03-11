<?php
require __DIR__ . '/../app/init.php';

if (!$Ouser->is_login()) {
    redirect("login.php");
}

$page = $_GET['page'] ?? 'procurement';
$actual_link = $page;

$inventory = [];
$error = null;

try {
    $stmt = $pdo->query("SELECT im.*, ic.name as category_name FROM inventory_master im LEFT JOIN inventory_categories ic ON im.category_id = ic.id ORDER BY im.item_name ASC");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Unable to load data: ' . $e->getMessage();
}
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" id="mainContent">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <h1 class="m-0 text-dark">Procurement / Deliveries</h1>
          <small class="text-muted">Record new supplies arriving in the inventory from procurement</small>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right mb-0">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Procurement</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <style>
        :root {
          --proc-border: #e5eaf4;
          --proc-soft-bg: linear-gradient(145deg, #f8fbff 0%, #eef4ff 100%);
          --proc-ink: #0f172a;
          --proc-muted: #64748b;
          --proc-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .proc-hero {
          border: 1px solid var(--proc-border);
          border-radius: 14px;
          padding: 16px;
          margin-bottom: 14px;
          background: var(--proc-soft-bg);
          box-shadow: var(--proc-shadow);
        }

        .proc-hero-title {
          margin: 0;
          color: var(--proc-ink);
          font-weight: 700;
          font-size: 1.05rem;
        }

        .proc-hero-sub {
          color: var(--proc-muted);
          margin: 4px 0 0;
          font-size: 0.86rem;
        }

        .proc-card {
          border: 1px solid var(--proc-border);
          border-radius: 14px;
          box-shadow: var(--proc-shadow);
          margin-bottom: 20px;
        }

        .proc-card .card-header {
          background: #fff;
          border-bottom: 1px solid var(--proc-border);
          padding: 13px 16px;
        }

        .proc-card .card-title {
          margin: 0;
          color: var(--proc-ink);
          font-size: 0.95rem;
          font-weight: 700;
        }

        .form-group {
          margin-bottom: 16px;
        }

        .form-label {
          display: block;
          font-size: 0.86rem;
          font-weight: 600;
          color: #374151;
          margin-bottom: 6px;
        }

        .form-control, .form-select {
          width: 100%;
          border: 1px solid #dbe4f2;
          border-radius: 9px;
          padding: 10px 12px;
          font-size: 0.86rem;
          color: #1f2937;
          background: #fff;
          outline: none;
          transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus, .form-select:focus {
          border-color: #93c5fd;
          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .row {
          display: flex;
          flex-wrap: wrap;
          margin-left: -8px;
          margin-right: -8px;
        }

        .col-md-6, .col-md-4 { padding-left: 8px; padding-right: 8px; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }

        @media (max-width: 767px) {
          .col-md-6, .col-md-4 { flex: 0 0 100%; max-width: 100%; }
        }

        .btn-primary {
          background: #059669;
          border: none;
          color: #fff;
          padding: 10px 20px;
          border-radius: 8px;
          font-size: 0.86rem;
          font-weight: 600;
          cursor: pointer;
          transition: background 0.2s;
        }

        .btn-primary:hover {
          background: #047857;
        }

        .btn-secondary {
          background: #6b7280;
          border: none;
          color: #fff;
          padding: 10px 20px;
          border-radius: 8px;
          font-size: 0.86rem;
          font-weight: 600;
          cursor: pointer;
        }

        .proc-table-wrap {
          overflow-x: auto;
          border: 1px solid #e8edf6;
          border-radius: 11px;
        }

        .proc-table {
          width: 100%;
          border-collapse: collapse;
        }

        .proc-table thead th {
          background: #f8fbff;
          color: #475569;
          font-size: 0.8rem;
          font-weight: 700;
          padding: 12px 16px;
          text-align: left;
          border-bottom: 1px solid #e7eef9;
          white-space: nowrap;
        }

        .proc-table tbody td {
          font-size: 0.86rem;
          color: #0f172a;
          padding: 12px 16px;
          border-bottom: 1px solid #f1f5f9;
        }

        .proc-table tbody tr:hover {
          background: #f8fafc;
        }

        .text-right { text-align: right; }

        .alert {
          padding: 12px 16px;
          border-radius: 8px;
          margin-bottom: 16px;
        }

        .alert-success {
          background: #dcfce7;
          border: 1px solid #bbf7d0;
          color: #166534;
        }

        .alert-error {
          background: #fef2f2;
          border: 1px solid #fecaca;
          color: #991b1b;
        }

        .empty-state {
          padding: 40px;
          text-align: center;
          color: #64748b;
        }

        .info-box {
          background: #f0fdf4;
          border: 1px solid #bbf7d0;
          border-radius: 8px;
          padding: 12px 16px;
          color: #166534;
          font-size: 0.86rem;
          margin-bottom: 16px;
        }
      </style>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <div class="proc-hero">
        <h2 class="proc-hero-title">New Delivery / Procurement</h2>
        <p class="proc-hero-sub">Record new supplies arriving in the inventory. Stock will be automatically added.</p>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="card proc-card">
            <div class="card-header">
              <h3 class="card-title">Delivery Form</h3>
            </div>
            <div class="card-body">
              <form id="procurementForm">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Inventory Item</label>
                      <select name="inventory_id" id="inventorySelect" class="form-select" required>
                        <option value="">Select Item</option>
                        <?php foreach ($inventory as $item): ?>
                          <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['unit_price']; ?>">
                            <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['category_name'] . ')'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Delivery Date</label>
                      <input type="date" name="delivery_date" id="deliveryDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Quantity Received</label>
                      <input type="number" name="quantity" id="quantityInput" class="form-control" min="1" value="1" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Unit Price (₱)</label>
                      <input type="number" name="unit_price" id="unitPriceInput" class="form-control" step="0.01" min="0" value="0">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Total Value (₱)</label>
                      <input type="text" id="totalValueDisplay" class="form-control" value="0.00" readonly style="background:#f9fafb;">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Supplier Name</label>
                      <input type="text" name="supplier_name" class="form-control" placeholder="Supplier name">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">PO Number</label>
                      <input type="text" name="po_number" class="form-control" placeholder="Purchase Order number">
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Received By</label>
                  <input type="text" name="received_by" class="form-control" placeholder="Name of person receiving">
                </div>

                <div class="form-group">
                  <label class="form-label">Notes</label>
                  <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                </div>

                <div class="text-right">
                  <button type="button" class="btn btn-secondary" onclick="document.getElementById('procurementForm').reset();">Clear</button>
                  <button type="submit" class="btn btn-primary">Record Delivery</button>
                </div>
              </form>
              
              <div id="procurementMessage"></div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="card proc-card">
            <div class="card-header">
              <h3 class="card-title">Current Stock</h3>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Available Stock</label>
                <div id="currentStockDisplay" class="form-control" style="background:#f9fafb;">Select an item</div>
              </div>
              <div class="info-box">
                <i class="fas fa-info-circle mr-2"></i>
                Enter the quantity received. The system will automatically add to the current stock.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card proc-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Recent Deliveries</h3>
        </div>
        <div class="card-body p-0">
          <div class="proc-table-wrap">
            <table class="proc-table" id="procurementsTable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Item</th>
                  <th>Supplier</th>
                  <th>PO Number</th>
                  <th class="text-right">Qty</th>
                  <th class="text-right">Unit Price</th>
                  <th class="text-right">Total Value</th>
                  <th>Received By</th>
                </tr>
              </thead>
              <tbody id="procurementsTbody">
                <tr>
                  <td colspan="8" class="empty-state">Loading...</td>
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
  const inventorySelect = document.getElementById('inventorySelect');
  const quantityInput = document.getElementById('quantityInput');
  const unitPriceInput = document.getElementById('unitPriceInput');
  const totalValueDisplay = document.getElementById('totalValueDisplay');
  const currentStockDisplay = document.getElementById('currentStockDisplay');
  const procurementForm = document.getElementById('procurementForm');
  const procurementMessage = document.getElementById('procurementMessage');
  const procurementsTbody = document.getElementById('procurementsTbody');

  let currentStock = 0;

  function formatMoney(value) {
    return '₱' + Number(value || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  async function updateStockInfo() {
    const selectedOption = inventorySelect?.options[inventorySelect.selectedIndex];
    const inventoryId = selectedOption?.value;
    
    if (!inventoryId) {
      currentStockDisplay.textContent = 'Select an item';
      currentStock = 0;
      return;
    }

    try {
      const response = await fetch('app/ajax/get_inventory_stock.php?id=' + inventoryId, { credentials: 'same-origin' });
      const result = await response.json();
      
      if (result.status === 'ok') {
        currentStock = parseInt(result.stock || 0);
        currentStockDisplay.textContent = currentStock + ' units';
        
        if (!unitPriceInput.value || unitPriceInput.value === '0') {
          unitPriceInput.value = result.price || 0;
        }
        updateTotal();
      }
    } catch (error) {
      currentStockDisplay.textContent = 'Error loading';
    }
  }

  function updateTotal() {
    const qty = parseInt(quantityInput?.value || 0);
    const price = parseFloat(unitPriceInput?.value || 0);
    totalValueDisplay.value = (qty * price).toFixed(2);
  }

  if (inventorySelect) {
    inventorySelect.addEventListener('change', updateStockInfo);
  }
  
  if (quantityInput) {
    quantityInput.addEventListener('input', updateTotal);
  }
  
  if (unitPriceInput) {
    unitPriceInput.addEventListener('input', updateTotal);
  }

  procurementForm?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(procurementForm);
    const data = Object.fromEntries(formData.entries());
    
    procurementMessage.innerHTML = '<div class="alert">Processing...</div>';
    
    try {
      const response = await fetch('app/action/add_procurement.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
        credentials: 'same-origin'
      });
      
      const result = await response.json();
      
      if (result.status === 'ok') {
        procurementMessage.innerHTML = '<div class="alert alert-success">' + (result.message || 'Delivery recorded successfully!') + '</div>';
        procurementForm.reset();
        updateStockInfo();
        loadProcurements();
      } else {
        procurementMessage.innerHTML = '<div class="alert alert-error">' + (result.message || 'Error recording delivery') + '</div>';
      }
    } catch (error) {
      procurementMessage.innerHTML = '<div class="alert alert-error">Error: ' + error.message + '</div>';
    }
  });

  function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  async function loadProcurements() {
    try {
      const response = await fetch('app/ajax/procurements_data.php', { credentials: 'same-origin' });
      const payload = await response.json();
      
      if (payload.status !== 'ok') {
        throw new Error(payload.message || 'Failed to load');
      }
      
      const items = payload.data || [];
      
      if (!items.length) {
        procurementsTbody.innerHTML = '<tr><td colspan="8" class="empty-state">No deliveries recorded yet.</td></tr>';
        return;
      }
      
      procurementsTbody.innerHTML = items.map(function(item) {
        return '<tr>' +
          '<td>' + (item.delivery_date ? new Date(item.delivery_date).toLocaleDateString() : '-') + '</td>' +
          '<td>' + escapeHtml(item.item_name || '') + '</td>' +
          '<td>' + escapeHtml(item.supplier_name || '-') + '</td>' +
          '<td>' + escapeHtml(item.po_number || '-') + '</td>' +
          '<td class="text-right">' + Number(item.quantity || 0).toLocaleString() + '</td>' +
          '<td class="text-right">' + formatMoney(item.unit_price) + '</td>' +
          '<td class="text-right">' + formatMoney(item.total_value) + '</td>' +
          '<td>' + escapeHtml(item.received_by || '-') + '</td>' +
        '</tr>';
      }).join('');
    } catch (error) {
      procurementsTbody.innerHTML = '<tr><td colspan="8" class="empty-state">Error loading: ' + escapeHtml(error.message) + '</td></tr>';
    }
  }

  loadProcurements();
})();
</script>
