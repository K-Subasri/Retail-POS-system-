<?php
$pageTitle = "Sales Reports";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

// Default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales summary
$query = "SELECT 
            COUNT(*) as total_sales, 
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_sale,
            MIN(total_amount) as min_sale,
            MAX(total_amount) as max_sale
          FROM sales 
          WHERE transaction_date BETWEEN :start_date AND :end_date + INTERVAL 1 DAY";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get sales by day for chart
$chartQuery = "SELECT 
                DATE(transaction_date) as date, 
                COUNT(*) as sales_count, 
                SUM(total_amount) as daily_total
              FROM sales 
              WHERE transaction_date BETWEEN :start_date AND :end_date + INTERVAL 1 DAY
              GROUP BY DATE(transaction_date)
              ORDER BY date";
$chartStmt = $db->prepare($chartQuery);
$chartStmt->bindParam(':start_date', $start_date);
$chartStmt->bindParam(':end_date', $end_date);
$chartStmt->execute();
$dailySales = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

// Get top products
$productsQuery = "SELECT 
                   p.name as product_name,
                   SUM(si.quantity) as total_quantity,
                   SUM(si.subtotal) as total_revenue
                 FROM sale_items si
                 JOIN products p ON si.product_id = p.product_id
                 JOIN sales s ON si.sale_id = s.sale_id
                 WHERE s.transaction_date BETWEEN :start_date AND :end_date + INTERVAL 1 DAY
                 GROUP BY p.product_id
                 ORDER BY total_revenue DESC
                 LIMIT 10";
$productsStmt = $db->prepare($productsQuery);
$productsStmt->bindParam(':start_date', $start_date);
$productsStmt->bindParam(':end_date', $end_date);
$productsStmt->execute();
$topProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h4>Sales Reports</h4>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="printReport" class="btn btn-outline-secondary">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="exportCSV" class="btn btn-outline-success">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </button>
            </div>
        </form>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Total Sales</h6>
                        <h2 class="card-text"><?php echo $summary['total_sales'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="card-text">₹<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Average Sale</h6>
                        <h2 class="card-text">₹<?php echo number_format($summary['avg_sale'] ?? 0, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Largest Sale</h6>
                        <h2 class="card-text">₹<?php echo number_format($summary['max_sale'] ?? 0, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Daily Sales Performance</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Top Products by Revenue</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="salesTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td class="text-end">₹<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Revenue Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Product Revenue Analysis</h5>
            </div>
            <div class="card-body">
                <canvas id="productRevenueChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales chart data
    const salesData = {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M j', strtotime($item['date'])) . "'"; }, $dailySales)); ?>],
        datasets: [
            {
                label: 'Number of Sales',
                data: [<?php echo implode(',', array_column($dailySales, 'sales_count')); ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            },
            {
                label: 'Daily Revenue (₹)',
                data: [<?php echo implode(',', array_column($dailySales, 'daily_total')); ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                type: 'line',
                yAxisID: 'y1'
            }
        ]
    };

    // Product Revenue Chart Data
    const productRevenueData = {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . addslashes($item['product_name']) . "'"; }, $topProducts)); ?>],
        datasets: [{
            label: 'Revenue (₹)',
            data: [<?php echo implode(',', array_column($topProducts, 'total_revenue')); ?>],
            backgroundColor: 'rgba(153, 102, 255, 0.6)',
            borderColor: 'rgba(153, 102, 255, 1)',
            borderWidth: 1,
            barPercentage: 0.8,
            categoryPercentage: 0.9
        }]
    };

    // Initialize sales chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: salesData,
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Number of Sales'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue (₹)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Initialize product revenue chart as histogram
    const productCtx = document.getElementById('productRevenueChart').getContext('2d');
    const productRevenueChart = new Chart(productCtx, {
        type: 'bar',
        data: productRevenueData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.raw.toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
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
                        text: 'Revenue (₹)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString('en-IN');
                        }
                    }
                }
            },
            maintainAspectRatio: false
        }
    });

    // Print functionality
    document.getElementById('printReport').addEventListener('click', function() {
        window.print();
    });

    // Export CSV functionality
    document.getElementById('exportCSV').addEventListener('click', function() {
        const table = document.getElementById('salesTable');
        const rows = Array.from(table.querySelectorAll('tr'));
        
        const csvContent = rows.map(row => {
            const cells = Array.from(row.querySelectorAll('th, td'));
            return cells.map(cell => `"${cell.textContent.trim()}"`).join(',');
        }).join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'sales_report_<?php echo date('Y-m-d'); ?>.csv';
        link.click();
    });
});
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none;
        box-shadow: none;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>