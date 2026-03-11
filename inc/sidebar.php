
<?php
$sessionUserName = isset($_SESSION['user_name']) && is_string($_SESSION['user_name']) ? trim($_SESSION['user_name']) : 'User';
$avatarInitial = strtoupper(substr($sessionUserName, 0, 1));
if ($avatarInitial === '') {
    $avatarInitial = 'U';
}
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-redesign" aria-label="Primary Navigation">

    <!-- Brand Logo -->
    <a href="index.php?page=dashboard" class="brand-link" aria-label="PRI Inventory Dashboard">
        <img src="dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3">
        <span class="brand-text font-weight-semibold">PRI Supplies</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <a href="#mainContent" class="sr-only sr-only-focusable">Skip navigation</a>

        <!-- Sidebar Menu -->
        <nav class="mt-2 flex-grow-1" aria-label="Sidebar Menu">
            <ul class="nav nav-pills nav-sidebar flex-column sidebar-menu-list" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-header sidebar-section-label" role="presentation">PRI INVENTORY</li>
                
                <li class="nav-item">
                    <a href="index.php?page=dashboard" title="Dashboard" class="nav-link <?php echo ($actual_link == 'dashboard' || $actual_link == '') ? 'active' : ''; ?>" <?php echo ($actual_link == 'dashboard' || $actual_link == '') ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="index.php?page=divisions" title="Divisions / Cost Centers" class="nav-link <?php echo $actual_link == 'divisions' ? 'active' : ''; ?>" <?php echo $actual_link == 'divisions' ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon fas fa-building"></i>
                        <p>Divisions / Cost Centers</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="index.php?page=inventory_masterlist" title="Inventory Masterlist" class="nav-link <?php echo $actual_link == 'inventory_masterlist' ? 'active' : ''; ?>" <?php echo $actual_link == 'inventory_masterlist' ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>Inventory Masterlist</p>
                    </a>
                </li>

                <li class="nav-header sidebar-section-label" role="presentation">OPERATIONS</li>
                
                <li class="nav-item">
                    <a href="index.php?page=monthly_issuances" title="Monthly Issuances" class="nav-link <?php echo $actual_link == 'monthly_issuances' ? 'active' : ''; ?>" <?php echo $actual_link == 'monthly_issuances' ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Monthly Issuances</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="index.php?page=procurement" title="Procurement / Deliveries" class="nav-link <?php echo $actual_link == 'procurement' ? 'active' : ''; ?>" <?php echo $actual_link == 'procurement' ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon fas fa-truck-loading"></i>
                        <p>Procurement / Deliveries</p>
                    </a>
                </li>

                <li class="nav-header sidebar-section-label" role="presentation">DATA MANAGEMENT</li>
                
                <li class="nav-item">
                    <a href="index.php?page=import_csv" title="Import CSV Data" class="nav-link <?php echo $actual_link == 'import_csv' ? 'active' : ''; ?>" <?php echo $actual_link == 'import_csv' ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon fas fa-file-import"></i>
                        <p>Import CSV Data</p>
                    </a>
                </li>

                <li class="nav-header sidebar-section-label" role="presentation">SYSTEM</li>
                <li class="nav-item">
                     <a href="index.php?page=backup_database" title="Backup Database" class="nav-link <?php echo $actual_link == 'backup_database' ? 'active' : ''; ?>" <?php echo $actual_link == 'backup_database' ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon fas fa-database"></i>
                        <p>Backup Database</p>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-user-footer">
            <a href="index.php?page=profile" class="sidebar-user-link" title="Open Profile">
                <div class="avatar-initial avatar-initial-md"><?php echo htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="sidebar-user-meta">
                    <span class="sidebar-user-name"><?php echo htmlspecialchars($sessionUserName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="sidebar-user-role"><?php echo isset($_SESSION['user_role']) ? htmlspecialchars(ucfirst((string) $_SESSION['user_role']), ENT_QUOTES, 'UTF-8') : 'User'; ?></span>
                </div>
                <i class="fas fa-chevron-right sidebar-user-arrow" aria-hidden="true"></i>
            </a>
        </div>
    </div>
    <!-- /.sidebar -->
</aside>
    </div>
    <?php require_once 'inc/member_add_modal.php'; ?>
    <?php require_once 'inc/catagory_modal.php'; ?>
    <?php require_once 'inc/suppliar_modal.php'; ?>
    <?php require_once 'inc/expense_catagory_modal.php'; ?>
