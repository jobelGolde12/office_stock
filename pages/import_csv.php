<?php
require __DIR__ . '/../app/init.php';

if (!$Ouser->is_login()) {
    redirect("login.php");
}

$page = $_GET['page'] ?? 'import_csv';
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
          <h1 class="m-0 text-dark">Import CSV Data</h1>
          <small class="text-muted">Import inventory items from RPCI CSV file</small>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right mb-0">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Import CSV</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <style>
        :root {
          --imp-border: #e5eaf4;
          --imp-soft-bg: linear-gradient(145deg, #f8fbff 0%, #eef4ff 100%);
          --imp-ink: #0f172a;
          --imp-muted: #64748b;
          --imp-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .imp-hero {
          border: 1px solid var(--imp-border);
          border-radius: 14px;
          padding: 16px;
          margin-bottom: 14px;
          background: var(--imp-soft-bg);
          box-shadow: var(--imp-shadow);
        }

        .imp-hero-title {
          margin: 0;
          color: var(--imp-ink);
          font-weight: 700;
          font-size: 1.05rem;
        }

        .imp-hero-sub {
          color: var(--imp-muted);
          margin: 4px 0 0;
          font-size: 0.86rem;
        }

        .imp-card {
          border: 1px solid var(--imp-border);
          border-radius: 14px;
          box-shadow: var(--imp-shadow);
          margin-bottom: 20px;
        }

        .imp-card .card-header {
          background: #fff;
          border-bottom: 1px solid var(--imp-border);
          padding: 13px 16px;
        }

        .imp-card .card-title {
          margin: 0;
          color: var(--imp-ink);
          font-size: 0.95rem;
          font-weight: 700;
        }

        .drop-zone {
          border: 2px dashed #cbd5e1;
          border-radius: 12px;
          padding: 40px;
          text-align: center;
          background: #f8fafc;
          transition: all 0.2s;
          cursor: pointer;
        }

        .drop-zone:hover, .drop-zone.dragover {
          border-color: #3b82f6;
          background: #eff6ff;
        }

        .drop-zone-icon {
          font-size: 3rem;
          color: #94a3b8;
          margin-bottom: 12px;
        }

        .drop-zone-text {
          color: #475569;
          font-size: 0.95rem;
        }

        .drop-zone-hint {
          color: #94a3b8;
          font-size: 0.85rem;
          margin-top: 8px;
        }

        .file-input {
          display: none;
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

        .btn-primary:disabled {
          background: #94a3b8;
          cursor: not-allowed;
        }

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

        .alert-info {
          background: #eff6ff;
          border: 1px solid #bfdbfe;
          color: #1e40af;
        }

        .preview-table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 16px;
          font-size: 0.85rem;
        }

        .preview-table th {
          background: #f8fbff;
          padding: 10px 12px;
          text-align: left;
          font-weight: 600;
          color: #475569;
          border-bottom: 1px solid #e7eef9;
        }

        .preview-table td {
          padding: 10px 12px;
          border-bottom: 1px solid #f1f5f9;
        }

        .preview-table tr:hover {
          background: #f8fafc;
        }

        .badge-category {
          display: inline-flex;
          padding: 3px 8px;
          border-radius: 999px;
          font-size: 0.7rem;
          font-weight: 600;
          background: #eef2ff;
          color: #4338ca;
        }

        .info-box {
          background: #f0fdf4;
          border: 1px solid #bbf7d0;
          border-radius: 8px;
          padding: 16px;
          color: #166534;
          font-size: 0.86rem;
          margin-bottom: 16px;
        }

        .info-box h4 {
          margin: 0 0 8px;
          font-size: 0.95rem;
          font-weight: 700;
        }

        .info-box ul {
          margin: 0;
          padding-left: 20px;
        }

        .info-box li {
          margin-bottom: 4px;
        }

        .spinner {
          display: inline-block;
          width: 16px;
          height: 16px;
          border: 2px solid #fff;
          border-top-color: transparent;
          border-radius: 50%;
          animation: spin 0.8s linear infinite;
          margin-right: 8px;
        }

        @keyframes spin {
          to { transform: rotate(360deg); }
        }

        .result-summary {
          display: flex;
          gap: 16px;
          margin-top: 16px;
          flex-wrap: wrap;
        }

        .result-item {
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          padding: 12px 16px;
          text-align: center;
        }

        .result-item .count {
          font-size: 1.5rem;
          font-weight: 700;
          color: #0f172a;
        }

        .result-item .label {
          font-size: 0.75rem;
          color: #64748b;
          text-transform: uppercase;
        }
      </style>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <div class="imp-hero">
        <h2 class="imp-hero-title">CSV Data Import</h2>
        <p class="imp-hero-sub">Upload a CSV file containing inventory items from the official RPCI Excel file.</p>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="card imp-card">
            <div class="card-header">
              <h3 class="card-title">Upload CSV File</h3>
            </div>
            <div class="card-body">
              <div class="drop-zone" id="dropZone">
                <div class="drop-zone-icon">
                  <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="drop-zone-text">
                  Drag and drop your CSV file here, or click to browse
                </div>
                <div class="drop-zone-hint">
                  Supported format: CSV (comma-separated values)
                </div>
                <input type="file" id="csvFileInput" class="file-input" accept=".csv">
              </div>

              <div id="fileInfo" style="display:none; margin-top:16px;"></div>

              <div id="importResult"></div>

              <div id="previewSection" style="display:none; margin-top:20px;">
                <h4 style="margin-bottom:12px; font-size:0.95rem; font-weight:600;">Data Preview</h4>
                <div style="overflow-x:auto;">
                  <table class="preview-table" id="previewTable">
                    <thead id="previewHead"></thead>
                    <tbody id="previewBody"></tbody>
                  </table>
                </div>
                <div style="margin-top:16px; text-align:right;">
                  <button type="button" class="btn btn-primary" id="importBtn" disabled>
                    <i class="fas fa-database mr-2"></i>Import Data
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="info-box">
            <h4><i class="fas fa-info-circle mr-2"></i>CSV Format Guide</h4>
            <p>The CSV file should contain the following columns:</p>
            <ul>
              <li><strong>Item Name</strong> - Name of the supply item</li>
              <li><strong>Category</strong> - Category (Office Supplies, ICT, Janitorial, Semi-Expendable)</li>
              <li><strong>Unit</strong> - Unit of measure (pcs, ream, gallon, etc.)</li>
              <li><strong>Unit Price</strong> - Price per unit</li>
              <li><strong>Quantity</strong> - Current stock quantity</li>
              <li><strong>Description</strong> - Optional description</li>
            </ul>
          </div>

          <div class="info-box" style="background:#eff6ff; border-color:#bfdbfe; color:#1e40af;">
            <h4><i class="fas fa-lightbulb mr-2"></i>Tips</h4>
            <ul style="color:#1e40af;">
              <li>First row should contain column headers</li>
              <li>Category will be matched automatically</li>
              <li>Existing items with same name will be updated</li>
              <li>Import in batches if file is large</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
(function() {
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('csvFileInput');
  const fileInfo = document.getElementById('fileInfo');
  const previewSection = document.getElementById('previewSection');
  const previewHead = document.getElementById('previewHead');
  const previewBody = document.getElementById('previewBody');
  const importBtn = document.getElementById('importBtn');
  const importResult = document.getElementById('importResult');

  let parsedData = [];
  let headers = [];

  dropZone?.addEventListener('click', function() {
    fileInput?.click();
  });

  dropZone?.addEventListener('dragover', function(e) {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });

  dropZone?.addEventListener('dragleave', function() {
    dropZone.classList.remove('dragover');
  });

  dropZone?.addEventListener('drop', function(e) {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length) {
      handleFile(files[0]);
    }
  });

  fileInput?.addEventListener('change', function() {
    if (this.files.length) {
      handleFile(this.files[0]);
    }
  });

  function handleFile(file) {
    if (!file.name.endsWith('.csv')) {
      showError('Please upload a CSV file');
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      parseCSV(e.target.result);
    };
    reader.readAsText(file);
  }

  function parseCSV(text) {
    const lines = text.split(/\r?\n/).filter(line => line.trim());
    if (lines.length < 2) {
      showError('CSV file is empty or has no data rows');
      return;
    }

    headers = parseCSVLine(lines[0]);
    parsedData = [];

    for (let i = 1; i < lines.length; i++) {
      const values = parseCSVLine(lines[i]);
      if (values.length > 0) {
        const row = {};
        headers.forEach((header, index) => {
          row[header.toLowerCase().trim()] = values[index] || '';
        });
        parsedData.push(row);
      }
    }

    showPreview();
  }

  function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuotes = false;
    
    for (let i = 0; i < line.length; i++) {
      const char = line[i];
      if (char === '"') {
        inQuotes = !inQuotes;
      } else if (char === ',' && !inQuotes) {
        result.push(current.trim());
        current = '';
      } else {
        current += char;
      }
    }
    result.push(current.trim());
    return result;
  }

  function showPreview() {
    fileInfo.innerHTML = '<div class="alert alert-info">' + 
      '<i class="fas fa-file-csv mr-2"></i>File loaded: ' + parsedData.length + ' rows found</div>';
    fileInfo.style.display = 'block';
    
    previewHead.innerHTML = '<tr>' + headers.map(h => '<th>' + escapeHtml(h) + '</th>').join('') + '</tr>';
    
    const previewRows = parsedData.slice(0, 5);
    previewBody.innerHTML = previewRows.map(row => {
      return '<tr>' + headers.map(h => '<td>' + escapeHtml(row[h.toLowerCase().trim()] || '') + '</td>').join('') + '</tr>';
    }).join('');
    
    if (parsedData.length > 5) {
      previewBody.innerHTML += '<tr><td colspan="' + headers.length + '" style="text-align:center;color:#64748b;">... and ' + (parsedData.length - 5) + ' more rows</td></tr>';
    }
    
    previewSection.style.display = 'block';
    importBtn.disabled = false;
  }

  importBtn?.addEventListener('click', async function() {
    importBtn.disabled = true;
    importBtn.innerHTML = '<span class="spinner"></span>Importing...';
    
    try {
      const response = await fetch('app/action/import_csv.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ data: parsedData, headers: headers }),
        credentials: 'same-origin'
      });
      
      const result = await response.json();
      
      if (result.status === 'ok') {
        showSuccess(result.message);
        if (result.summary) {
          importResult.innerHTML += '<div class="result-summary">' +
            '<div class="result-item"><div class="count">' + (result.summary.inserted || 0) + '</div><div class="label">Inserted</div></div>' +
            '<div class="result-item"><div class="count">' + (result.summary.updated || 0) + '</div><div class="label">Updated</div></div>' +
            '<div class="result-item"><div class="count">' + (result.summary.skipped || 0) + '</div><div class="label">Skipped</div></div>' +
          '</div>';
        }
        previewSection.style.display = 'none';
        fileInfo.style.display = 'none';
      } else {
        showError(result.message || 'Import failed');
      }
    } catch (error) {
      showError('Error: ' + error.message);
    }
    
    importBtn.disabled = false;
    importBtn.innerHTML = '<i class="fas fa-database mr-2"></i>Import Data';
  });

  function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function showError(message) {
    importResult.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-triangle mr-2"></i>' + escapeHtml(message) + '</div>';
  }

  function showSuccess(message) {
    importResult.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>' + escapeHtml(message) + '</div>';
  }
})();
</script>
