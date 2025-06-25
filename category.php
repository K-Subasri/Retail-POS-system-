<?php
$pageTitle = "Category Products";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category details
$stmt = $db->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$category) {
    header("Location: index.php");
    exit;
}

// Get products in this category
$stmt = $db->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY name");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Products in <?php echo htmlspecialchars($category['name']); ?></h4>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Categories
        </a>
    </div>
    <div class="card-body">
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php foreach($products as $product): ?>
            <div class="col">
                <div class="card h-100">
                    <?php if($product['image_path']): ?>
                    <img src="../../<?php echo $product['image_path']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         style="height: 200px; object-fit: cover; cursor: pointer;"
                         onclick="showProductDetails(<?php echo $product['product_id']; ?>)">
                    <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                         style="height: 200px; cursor: pointer;"
                         onclick="showProductDetails(<?php echo $product['product_id']; ?>)">
                        <i class="fas fa-box fa-3x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text">
                            <strong>Price:</strong> ₹<?php echo number_format($product['price'], 2); ?><br>
                            <strong>Stock:</strong> <?php echo $product['quantity']; ?>
                        </p>
                        <button class="btn btn-primary w-100" onclick="showProductDetails(<?php echo $product['product_id']; ?>)">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
function showProductDetails(productId) {
    // Show loading state
    document.getElementById('productDetails').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
    
    // Load product details
    fetch('get_product.php?id=' + productId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('productDetails').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('productDetails').innerHTML = '<div class="alert alert-danger">Error loading product details</div>';
        });
}
</script>

<?php require_once '../../includes/footer.php'; ?> 