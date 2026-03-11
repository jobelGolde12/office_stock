<?php
require __DIR__ . '/../app/init.php';

if (!$Ouser->is_login()) {
    redirect("login.php");
}

$page = $_GET['page'] ?? 'monthly_issuances';
$actual_link = $page;

$divisions = [];
$inventory = [];
$error = null;

try {
    $stmt = $pdo->query("SELECT * FROM divisions WHERE is_active = 1 ORDER BY code ASC");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT im.*, ic.name as category_name FROM inventory_master im LEFT JOIN inventory_categories ic ON im.category_id = ic.id WHERE im.stock_quantity > 0 ORDER BY im.item_name ASC");
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
          <h1 class="m-0 text-dark">Monthly Issuances</h1>
          <small class="text-muted">Record supplies issued to divisions (RSMI - Report of Supplies and Materials Issued)</small>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right mb-0">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Monthly Issuances</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <style>
        :root {
          --iss-border: #e5eaf4;
          --iss-soft-bg: linear-gradient(145deg, #f8fbff 0%, #eef4ff 100%);
          --iss-ink: #0f172a;
          --iss-muted: #64748b;
          --iss-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .iss-hero {
          border: 1px solid var(--iss-border);
          border-radius: 14px;
          padding: 16px;
          margin-bottom: 14px;
          background: var(--iss-soft-bg);
          box-shadow: var(--iss-shadow);
        }

        .iss-hero-title {
          margin: 0;
          color: var(--iss-ink);
          font-weight: 700;
          font-size: 1.05rem;
        }

        .iss-hero-sub {
          color: var(--iss-muted);
          margin: 4px 0 0;
          font-size: 0.86rem;
        }

        .iss-card {
          border: 1px solid var(--iss-border);
          border-radius: 14px;
          box-shadow: var(--iss-shadow);
          margin-bottom: 20px;
        }

        .iss-card .card-header {
          background: #fff;
          border-bottom: 1px solid var(--iss-border);
          padding: 13px 16px;
        }

        .iss-card .card-title {
          margin: 0;
          color: var(--iss-ink);
          font-size: 0.95rem;
          font-weight: 700;
        }

        .iss-form {
          padding: 16px;
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

        .col-md-6, .col-md-4, .col-md-3 {
          padding-left: 8px;
          padding-right: 8px;
        }

        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; }

        @media (max-width: 767px) {
          .col-md-6, .col-md-4, .col-md-3 {
            flex: 0 0 100%;
            max-width: 100%;
          }
        }

        .btn-primary {
          background: #2563eb;
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
          background: #1d4ed8;
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

        .iss-table-wrap {
          overflow-x: auto;
          border: 1px solid #e8edf6;
          border-radius: 11px;
        }

        .iss-table {
          width: 100%;
          border-collapse: collapse;
        }

        .iss-table thead th {
          background: #f8fbff;
          color: #475569;
          font-size: 0.8rem;
          font-weight: 700;
          padding: 12px 16px;
          text-align: left;
          border-bottom: 1px solid #e7eef9;
          white-space: nowrap;
        }

        .iss-table tbody td {
          font-size: 0.86rem;
          color: #0f172a;
          padding: 12px 16px;
          border-bottom: 1px solid #f1f5f9;
        }

        .iss-table tbody tr:hover {
          background: #f8fafc;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

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

        .stock-info {
          font-size: 0.85rem;
          color: #64748b;
          margin-top: 4px;
        }
      </style>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <div class="iss-hero">
        <h2 class="iss-hero-title">New Issuance</h2>
        <p class="iss-hero-sub">Record supplies issued to a division. Stock will be automatically deducted.</p>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="card iss-card">
            <div class="card-header">
              <h3 class="card-title">Issuance Form</h3>
            </div>
            <div class="card-body">
              <form id="issuanceForm" class="iss-form">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Division / Cost Center</label>
                      <select name="division_id" id="divisionSelect" class="form-select" required>
                        <option value="">Select Division</option>
                        <?php foreach ($divisions as $div): ?>
                          <option value="<?php echo $div['id']; ?>">
                            <?php echo htmlspecialchars($div['code'] . ' - ' . $div['name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Issuance Date</label>
                      <input type="date" name="issuance_date" id="issuanceDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Inventory Item</label>
                      <select name="inventory_id" id="inventorySelect" class="form-select" required>
                        <option value="">Select Item</option>
                        <?php foreach ($inventory as $item): ?>
                          <option value="<?php echo $item['id']; ?>" data-stock="<?php echo $item['stock_quantity']; ?>" data-price="<?php echo $item['unit_price']; ?>">
                            <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['category_name'] . ') - Stock: ' . $item['stock_quantity']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <div class="stock-info" id="stockInfo">Select an item to see available stock</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-label">Quantity</label>
                      <input type="number" name="quantity" id="quantityInput" class="form-control" min="1" value="1" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Issued By</label>
                      <input type="text" name="issued_by" class="form-control" placeholder="Name of issuer">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">Received By</label>
                      <input type="text" name="received_by" class="form-control" placeholder="Name of receiver">
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Notes</label>
                  <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                </div>

                <div class="text-right">
                  <button type="button" class="btn btn-secondary" onclick="document.getElementById('issuanceForm').reset();">Clear</button>
                  <button type="submit" class="btn btn-primary">Record Issuance</button>
                </div>
              </form>
              
              <div id="issuanceMessage"></div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="card iss-card">
            <div class="card-header">
              <h3 class="card-title">Summary</h3>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Unit Price</label>
                <div id="unitPriceDisplay" class="form-control" style="background:#f9fafb;">₱0.00</div>
              </div>
              <div class="form-group">
                <label class="form-label">Total Value</label>
                <div id="totalValueDisplay" class="form-control" style="background:#f9fafb;">₱0.00</div>
              </div>
              <div class="form-group">
                <label class="form-label">Available Stock</label>
                <div id="availableStockDisplay" class="form-control" style="background:#f9fafb;">0</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card iss-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Recent Issuances</h3>
        </div>
        <div class="card-body p-0">
          <div class="iss-table-wrap">
            <table class="iss-table" id="issuancesTable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Division</th>
                  <th>Item</th>
                  <th class="text-right">Qty</th>
                  <th class="text-right">Unit Price</th>
                  <th class="text-right">Total Value</th>
                  <th>Received By</th>
                </tr>
              </thead>
              <tbody id="issuancesTbody">
                <tr>
                  <td colspan="7" class="empty-state">Loading...</td>
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
  const stockInfo = document.getElementById('stockInfo');
  const unitPriceDisplay = document.getElementById('unitPriceDisplay');
  const totalValueDisplay = document.getElementById('totalValueDisplay');
  const availableStockDisplay = document.getElementById('availableStockDisplay');
  const issuanceForm = document.getElementById('issuanceForm');
  const issuanceMessage = document.getElementById('issuanceMessage');
  const issuancesTbody = document.getElementById('issuancesTbody');

  function formatMoney(value) {
    return '₱' + Number(value || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function updateSummary() {
    const selectedOption = inventorySelect?.options[inventorySelect.selectedIndex];
    const stock = parseInt(selectedOption?.dataset?.stock || 0);
    const price = parseFloat(selectedOption?.dataset?.price || 0);
    const qty = parseInt(quantityInput?.value || 0);
    
    if (selectedOption && selectedOption.value) {
      stockInfo.textContent = `Available: ${stock} units`;
      availableStockDisplay.textContent = stock;
      unitPriceDisplay.textContent = formatMoney(price);
      totalValueDisplay.textContent = formatMoney(price * qty);
    } else {
      stockInfo.textContent = 'Select an item to see available stock';
      availableStockDisplay.textContent = '0';
      unitPriceDisplay.textContent = '₱0.00';
      totalValueDisplay.textContent = '₱0.00';
    }
  }

  if (inventorySelect) {
    inventorySelect.addEventListener('change', updateSummary);
  }
  
  if (quantityInput) {
    quantityInput.addEventListener('input', updateSummary);
  }

  issuanceForm?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(issuanceForm);
    const data = Object.fromEntries(formData.entries());
    
    issuanceMessage.innerHTML = '<div class="alert">Processing...</div>';
    
    try {
      const response = await fetch('app/action/add_issuance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
        credentials: 'same-origin'
      });
      
      const result = await response.json();
      
      if (result.status === 'ok') {
        issuanceMessage.innerHTML = '<div class="alert alert-success">' + (result.message || 'Issuance recorded successfully!') + '</div>';
        issuanceForm.reset();
        updateSummary();
        loadIssuances();
      } else {
        issuanceMessage.innerHTML = '<div class="alert alert-error">' + (result.message || 'Error recording issuance') + '</div>';
      }
    } catch (error) {
      issuanceMessage.innerHTML = '<div class="alert alert-error">Error: ' + error.message + '</div>';
    }
  });

  function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  async function loadIssuances() {
    try {
      const response = await fetch('app/ajax/issuances_data.php', { credentials: 'same-origin' });
      const payload = await response.json();
      
      if (payload.status !== 'ok') {
        throw new Error(payload.message || 'Failed to load');
      }
      
      const items = payload.data || [];
      
      if (!items.length) {
        issuancesTbody.innerHTML = '<tr><td colspan="7" class="empty-state">No issuances recorded yet.</td></tr>';
        return;
      }
      
      issuancesTbody.innerHTML = items.map(function(item) {
        return '<tr>' +
          '<td>' + (item.issuance_date ? new Date(item.issuance_date).toLocaleDateString() : '-') + '</td>' +
          '<td>' + escapeHtml(item.division_code || '') + '</td>' +
          '<td>' + escapeHtml(item.item_name || '') + '</td>' +
          '<td class="text-right">' + Number(item.quantity || 0).toLocaleString() + '</td>' +
          '<td class="text-right">' + formatMoney(item.unit_price) + '</td>' +
          '<td class="text-right">' + formatMoney(item.total_value) + '</td>' +
          '<td>' + escapeHtml(item.received_by || '-') + '</td>' +
        '</tr>';
      }).join('');
    } catch (error) {
      issuancesTbody.innerHTML = '<tr><td colspan="7" class="empty-state">Error loading: ' + escapeHtml(error.message) + '</td></tr>';
    }
  }

  loadIssuances();
})();
</script>
