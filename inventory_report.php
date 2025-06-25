<?php
$pageTitle = "Inventory Reports";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

// Get inventory summary
$summaryQuery = "SELECT 
                  COUNT(*) as total_products,
                  SUM(quantity) as total_items,
                  SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_items,
                  SUM(price * quantity) as inventory_value
                FROM products";
$summaryStmt = $db->query($summaryQuery);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

// Get low stock items
$lowStockQuery = "SELECT * FROM products 
                 WHERE quantity <= reorder_level 
                 ORDER BY quantity ASC";
$lowStockStmt = $db->query($lowStockQuery);
$lowStockItems = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

// Get product stock levels for chart
$stockLevelsQuery = "SELECT name, quantity, reorder_level 
                    FROM products 
                    ORDER BY quantity DESC 
                    LIMIT 10";
$stockLevelsStmt = $db->query($stockLevelsQuery);
$stockLevels = $stockLevelsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent inventory movements
$movementsQuery = "SELECT 
                    il.log_id,
                    p.name as product_name,
                    p.barcode,
                    il.quantity_change,
                    il.previous_quantity,
                    il.new_quantity,
                    il.reason,
                    il.log_date,
                    u.username
                  FROM inventory_logs il
                  JOIN products p ON il.product_id = p.product_id
                  JOIN users u ON il.user_id = u.user_id
                  ORDER BY il.log_date DESC
                  LIMIT 10";
$movementsStmt = $db->query($movementsQuery);
$recentMovements = $movementsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h4>Inventory Reports</h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Total Products</h6>
                        <h2 class="card-text"><?php echo $summary['total_products']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Total Items</h6>
                        <h2 class="card-text"><?php echo $summary['total_items']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Low Stock Items</h6>
                        <h2 class="card-text"><?php echo $summary['low_stock_items']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Inventory Value</h6>
                        <h2 class="card-text">₹<?php echo number_format($summary['inventory_value'] ?? 0, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Levels Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Product Stock Levels Analysis</h5>
            </div>
            <div class="card-body">
                <canvas id="stockLevelsChart" height="300"></canvas>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Low Stock Items (Need Reordering)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Barcode</th>
                                        <th class="text-end">Current Stock</th>
                                        <th class="text-end">Reorder Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($lowStockItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                                        <td class="text-end <?php echo $item['quantity'] == 0 ? 'text-danger' : ''; ?>">
                                            <?php echo $item['quantity']; ?>
                                        </td>
                                        <td class="text-end"><?php echo $item['reorder_level']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($lowStockItems)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No low stock items</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Recent Inventory Movements</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Change</th>
                                        <th>Reason</th>
                                        <th>User</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentMovements as $movement): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                        <td class="text-end <?php echo $movement['quantity_change'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $movement['quantity_change'] > 0 ? '+' : ''; ?><?php echo $movement['quantity_change']; ?>
                                        </td>
                                        <td><?php echo ucfirst($movement['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($movement['username']); ?></td>
                                        <td><?php echo date('M j, H:i', strtotime($movement['log_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Stock Levels Chart
    const stockLevelsData = {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . addslashes($item['name']) . "'"; }, $stockLevels)); ?>],
        datasets: [{
            label: 'Current Stock',
            data: [<?php echo implode(',', array_column($stockLevels, 'quantity')); ?>],
            backgroundColor: 'rgba(255, 182, 193, 0.6)', // Light pink
            borderColor: 'rgba(255, 182, 193, 1)',
            borderWidth: 1,
            barPercentage: 0.8,
            categoryPercentage: 0.9
        },
        {
            label: 'Reorder Level',
            data: [<?php echo implode(',', array_column($stockLevels, 'reorder_level')); ?>],
            type: 'line',
            borderColor: 'rgba(255, 105, 180, 1)', // Hot pink
            borderWidth: 2,
            fill: false,
            pointStyle: 'dash'
        }]
    };

    const stockCtx = document.getElementById('stockLevelsChart').getContext('2d');
    const stockLevelsChart = new Chart(stockCtx, {
        type: 'bar',
        data: stockLevelsData,
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantity'
                    }
                }
            },
            maintainAspectRatio: false
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>