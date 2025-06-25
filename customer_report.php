<?php
$pageTitle = "Customer Reports";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

// Get customer statistics
$query = "SELECT 
            COUNT(DISTINCT c.customer_id) as total_customers,
            COUNT(DISTINCT CASE WHEN s.sale_id IS NOT NULL THEN c.customer_id END) as active_customers,
            AVG(CASE WHEN s.sale_id IS NOT NULL THEN 
                (SELECT COUNT(*) FROM sales WHERE customer_id = c.customer_id)
            ELSE 0 END) as avg_purchases,
            MAX(CASE WHEN s.sale_id IS NOT NULL THEN 
                (SELECT COUNT(*) FROM sales WHERE customer_id = c.customer_id)
            ELSE 0 END) as max_purchases
          FROM customers c
          LEFT JOIN sales s ON c.customer_id = s.customer_id";
$stmt = $db->prepare($query);
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get top customers by purchase amount
$topCustomersQuery = "SELECT 
    c.customer_id,
    c.name,
                        c.email,
                        c.phone,
                        COUNT(s.sale_id) as total_orders,
    SUM(s.total_amount) as total_spent,
    MAX(s.transaction_date) as last_purchase
    FROM customers c
    LEFT JOIN sales s ON c.customer_id = s.customer_id
    GROUP BY c.customer_id
                      ORDER BY total_spent DESC
                      LIMIT 10";
$topCustomersStmt = $db->prepare($topCustomersQuery);
$topCustomersStmt->execute();
$topCustomers = $topCustomersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer purchase distribution
$purchaseDistributionQuery = "SELECT 
                                CASE 
                                    WHEN purchase_count = 0 THEN 'No Purchases'
                                    WHEN purchase_count = 1 THEN '1 Purchase'
                                    WHEN purchase_count BETWEEN 2 AND 5 THEN '2-5 Purchases'
                                    WHEN purchase_count BETWEEN 6 AND 10 THEN '6-10 Purchases'
                                    ELSE '10+ Purchases'
                                END as purchase_range,
                                COUNT(*) as customer_count
                              FROM (
                                SELECT c.customer_id, COUNT(s.sale_id) as purchase_count
                                FROM customers c
                                LEFT JOIN sales s ON c.customer_id = s.customer_id
                                GROUP BY c.customer_id
                              ) as customer_purchases
                              GROUP BY purchase_range
                              ORDER BY 
                                CASE purchase_range
                                    WHEN 'No Purchases' THEN 1
                                    WHEN '1 Purchase' THEN 2
                                    WHEN '2-5 Purchases' THEN 3
                                    WHEN '6-10 Purchases' THEN 4
                                    ELSE 5
                                END";
$distributionStmt = $db->prepare($purchaseDistributionQuery);
$distributionStmt->execute();
$purchaseDistribution = $distributionStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h4>Customer Reports</h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
                <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Total Customers</h6>
                        <h2 class="card-text"><?php echo $summary['total_customers'] ?? 0; ?></h2>
                    </div>
                </div>
                </div>
                <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Active Customers</h6>
                        <h2 class="card-text"><?php echo $summary['active_customers'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Average Purchases</h6>
                        <h2 class="card-text"><?php echo number_format($summary['avg_purchases'] ?? 0, 1); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Max Purchases</h6>
                        <h2 class="card-text"><?php echo $summary['max_purchases'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Customer Purchase Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="purchaseDistributionChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Top Customers by Revenue</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="topCustomersChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Top Customers Details</h5>
                <div>
                    <button type="button" id="printReport" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button type="button" id="exportCSV" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="customersTable">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-end">Total Orders</th>
                                <th class="text-end">Total Spent</th>
                                <th>Last Purchase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($topCustomers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td class="text-end"><?php echo $customer['total_orders']; ?></td>
                                <td class="text-end">₹<?php echo number_format((float)($customer['total_spent'] ?? 0), 2); ?></td>
                                <td><?php echo $customer['last_purchase'] ? date('M d, Y', strtotime($customer['last_purchase'])) : 'Never'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to update chart colors based on dark mode
    function updateChartColors(isDarkMode) {
        const textColor = isDarkMode ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.9)';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        const tooltipBg = isDarkMode ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 0.8)';
        const tooltipColor = isDarkMode ? '#fff' : '#000';

        // Update both charts
        [distributionCtx.chart, customersCtx.chart].forEach(chart => {
            if (chart) {
                chart.options.plugins.legend.labels.color = textColor;
                chart.options.plugins.tooltip.backgroundColor = tooltipBg;
                chart.options.plugins.tooltip.titleColor = tooltipColor;
                chart.options.plugins.tooltip.bodyColor = tooltipColor;
                
                if (chart.options.scales) {
                    chart.options.scales.x.ticks.color = textColor;
                    chart.options.scales.x.grid.color = gridColor;
                    chart.options.scales.y.ticks.color = textColor;
                    chart.options.scales.y.grid.color = gridColor;
                }
                
                chart.update();
            }
        });
    }

    // Check initial dark mode state
    const isDarkMode = document.body.classList.contains('dark-mode');

    // Purchase Distribution Chart
    const distributionCtx = document.getElementById('purchaseDistributionChart').getContext('2d');
    const distributionChart = new Chart(distributionCtx, {
        type: 'pie',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['purchase_range'] . "'"; }, $purchaseDistribution)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_column($purchaseDistribution, 'customer_count')); ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.9)',     // No Purchases - Bright Red
                    'rgba(0, 123, 255, 0.9)',      // 1 Purchase - Bright Blue
                    'rgba(255, 193, 7, 0.9)',      // 2-5 Purchases - Bright Yellow
                    'rgba(40, 167, 69, 0.9)',      // 6-10 Purchases - Bright Green
                    'rgba(111, 66, 193, 0.9)'      // 10+ Purchases - Bright Purple
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(0, 123, 255, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(111, 66, 193, 1)'
                ],
                borderWidth: 3,
                hoverOffset: 20,
                hoverBorderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 25,
                        font: {
                            size: 13,
                            weight: 'bold'
                        },
                        color: isDarkMode ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.9)',
                        generateLabels: function(chart) {
                            const data = chart.data;
                            return data.labels.map((label, i) => ({
                                text: `${label} (${data.datasets[0].data[i]})`,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                strokeStyle: data.datasets[0].borderColor[i],
                                lineWidth: 3,
                                hidden: isNaN(data.datasets[0].data[i]),
                                index: i
                            }));
                        }
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 0.8)',
                    titleColor: isDarkMode ? '#fff' : '#000',
                    bodyColor: isDarkMode ? '#fff' : '#000',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} customers (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Top Customers Chart
    const customersCtx = document.getElementById('topCustomersChart').getContext('2d');
    const customersChart = new Chart(customersCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . addslashes($item['name']) . "'"; }, $topCustomers)); ?>],
            datasets: [{
                label: 'Total Spent (₹)',
                data: [<?php echo implode(',', array_column($topCustomers, 'total_spent')); ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.9)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: isDarkMode ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.9)',
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: isDarkMode ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.9)',
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: isDarkMode ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.9)'
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 0.8)',
                    titleColor: isDarkMode ? '#fff' : '#000',
                    bodyColor: isDarkMode ? '#fff' : '#000',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Store chart instances for dark mode updates
    distributionCtx.chart = distributionChart;
    customersCtx.chart = customersChart;

    // Listen for dark mode changes
    document.addEventListener('darkModeChanged', function(e) {
        updateChartColors(e.detail.isDarkMode);
    });

    // Print report functionality
    document.getElementById('printReport').addEventListener('click', function() {
        window.print();
    });

    // Export to CSV functionality
    document.getElementById('exportCSV').addEventListener('click', function() {
        const table = document.getElementById('customersTable');
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        // Get headers
        const headers = [];
        table.querySelectorAll('th').forEach(th => {
            headers.push(th.innerText.trim());
        });
        csv.push(headers.join(','));
        
        // Get data rows
        rows.forEach(row => {
            if (row.querySelector('th')) return; // Skip header row
            
            const rowData = [];
            row.querySelectorAll('td').forEach(td => {
                rowData.push(td.innerText.trim().replace(/,/g, ';'));
            });
            csv.push(rowData.join(','));
        });
        
        // Create and download CSV file
        const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'customer_report.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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

/* Dark mode specific styles */
body.dark-mode {
    background-color: #121212;
    color: #e0e0e0;
}

body.dark-mode .card {
    background-color: #1e1e1e;
    border-color: #333;
}

body.dark-mode .card-header {
    background-color: #252525;
    border-bottom-color: #333;
    color: #f0f0f0;
}

body.dark-mode .table {
    color: #e0e0e0;
}

body.dark-mode .table thead th {
    background-color: #333;
    color: #ffffff;
    border-bottom-color: #444;
}

body.dark-mode .table td {
    border-top-color: #333;
}

body.dark-mode .table-hover tbody tr:hover {
    background-color: rgba(67, 97, 238, 0.1);
}

body.dark-mode .btn-outline-secondary {
    color: #aaa;
    border-color: #555;
}

body.dark-mode .btn-outline-secondary:hover {
    background-color: #555;
    color: #fff;
}

body.dark-mode .btn-outline-success {
    color: #28a745;
    border-color: #28a745;
}

body.dark-mode .btn-outline-success:hover {
    background-color: #28a745;
    color: #fff;
}

/* Chart specific dark mode styles */
body.dark-mode canvas {
    filter: brightness(0.9);
}

/* Chart container styles */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
</style>

<?php require_once '../../includes/footer.php'; ?>