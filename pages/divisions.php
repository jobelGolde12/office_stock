<?php
require __DIR__ . '/../app/init.php';

if (!$Ouser->is_login()) {
    redirect("login.php");
}

$page = $_GET['page'] ?? 'divisions';
$actual_link = $page;

$divisions = [];
$error = null;

try {
    $stmt = $pdo->query("SELECT * FROM divisions ORDER BY code ASC");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Unable to load divisions: ' . $e->getMessage();
}
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" id="mainContent">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <h1 class="m-0 text-dark">Divisions / Cost Centers</h1>
          <small class="text-muted">Manage PRI divisions and cost centers for inventory tracking</small>
        </div>
        <div class="col-sm-5">
          <ol class="breadcrumb float-sm-right mb-0">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Divisions</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <style>
        :root {
          --div-border: #e5eaf4;
          --div-soft-bg: linear-gradient(145deg, #f8fbff 0%, #eef4ff 100%);
          --div-ink: #0f172a;
          --div-muted: #64748b;
          --div-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .div-hero {
          border: 1px solid var(--div-border);
          border-radius: 14px;
          padding: 16px;
          margin-bottom: 14px;
          background: var(--div-soft-bg);
          box-shadow: var(--div-shadow);
        }

        .div-hero-title {
          margin: 0;
          color: var(--div-ink);
          font-weight: 700;
          font-size: 1.05rem;
        }

        .div-hero-sub {
          color: var(--div-muted);
          margin: 4px 0 0;
          font-size: 0.86rem;
        }

        .div-card {
          border: 1px solid var(--div-border);
          border-radius: 14px;
          box-shadow: var(--div-shadow);
        }

        .div-card .card-header {
          background: #fff;
          border-bottom: 1px solid var(--div-border);
          padding: 13px 16px;
        }

        .div-card .card-title {
          margin: 0;
          color: var(--div-ink);
          font-size: 0.95rem;
          font-weight: 700;
        }

        .div-table-wrap {
          overflow-x: auto;
          border: 1px solid #e8edf6;
          border-radius: 11px;
        }

        .div-table {
          width: 100%;
          border-collapse: collapse;
        }

        .div-table thead th {
          background: #f8fbff;
          color: #475569;
          font-size: 0.8rem;
          font-weight: 700;
          padding: 12px 16px;
          text-align: left;
          border-bottom: 1px solid #e7eef9;
        }

        .div-table tbody td {
          font-size: 0.86rem;
          color: #0f172a;
          padding: 12px 16px;
          border-bottom: 1px solid #f1f5f9;
        }

        .div-table tbody tr:hover {
          background: #f8fafc;
        }

        .badge-div {
          display: inline-flex;
          align-items: center;
          padding: 4px 10px;
          border-radius: 999px;
          font-size: 0.75rem;
          font-weight: 600;
        }

        .badge-active {
          background: #dcfce7;
          color: #166534;
        }

        .badge-inactive {
          background: #f1f5f9;
          color: #64748b;
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
      </style>

      <?php if ($error): ?>
        <div class="alert-error">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <div class="div-hero">
        <h2 class="div-hero-title">PRI Divisions / Cost Centers</h2>
        <p class="div-hero-sub">These are the official divisions that request and consume supplies. Each division has its own issuance records for RSMI reporting.</p>
      </div>

      <div class="card div-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Division List</h3>
          <span class="badge-div badge-active"><?php echo count($divisions); ?> Divisions</span>
        </div>
        <div class="card-body p-0">
          <div class="div-table-wrap">
            <?php if (empty($divisions)): ?>
              <div class="empty-state">
                <i class="fas fa-building fa-2x mb-3"></i>
                <p>No divisions found. Run the setup to initialize divisions.</p>
              </div>
            <?php else: ?>
              <table class="div-table">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Division Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($divisions as $div): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($div['code'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                      <td><?php echo htmlspecialchars($div['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($div['description'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <span class="badge-div <?php echo ($div['is_active'] ?? 1) ? 'badge-active' : 'badge-inactive'; ?>">
                          <?php echo ($div['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                        </span>
                      </td>
                      <td><?php echo !empty($div['created_at']) ? date('M d, Y', strtotime($div['created_at'])) : '-'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
