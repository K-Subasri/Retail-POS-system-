<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_page, 'sales') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/sales/">
                <i class="fas fa-shopping-cart"></i>
                <span>Sales</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_page, 'inventory') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/inventory/">
                <i class="fas fa-boxes"></i>
                <span>Inventory</span>
            </a>
        </li>

        <?php if($auth->hasRole('admin')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_page, 'products') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/products/">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_page, 'categories') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/categories/">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_page, 'customers') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/customers/">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_page, 'users') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/users/">
                <i class="fas fa-user-cog"></i>
                <span>Users</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo strpos($current_page, 'reports') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>modules/reports/">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div> 