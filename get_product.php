<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product details
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.category_id 
                      WHERE p.product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$product) {
    echo '<div class="alert alert-danger">Product not found</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <?php if($product['image_path']): ?>
        <img src="../../<?php echo $product['image_path']; ?>" 
             class="img-fluid rounded" 
             alt="<?php echo htmlspecialchars($product['name']); ?>">
        <?php else: ?>
        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 300px;">
            <i class="fas fa-box fa-5x text-muted"></i>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
        <p class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
        
        <div class="mb-3">
            <strong>Barcode:</strong> <?php echo htmlspecialchars($product['barcode']); ?>
        </div>
        
        <div class="mb-3">
            <strong>Description:</strong><br>
            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <strong>Price:</strong><br>
                <h5 class="text-primary">₹<?php echo number_format($product['price'], 2); ?></h5>
            </div>
            <div class="col-6">
                <strong>Cost:</strong><br>
                <h5 class="text-muted">₹<?php echo number_format($product['cost'], 2); ?></h5>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <strong>Current Stock:</strong><br>
                <h5 class="<?php echo $product['quantity'] <= $product['reorder_level'] ? 'text-danger' : 'text-success'; ?>">
                    <?php echo $product['quantity']; ?>
                </h5>
            </div>
            <div class="col-6">
                <strong>Reorder Level:</strong><br>
                <h5 class="text-warning"><?php echo $product['reorder_level']; ?></h5>
            </div>
        </div>
        
        <?php if($auth->hasRole('admin')): ?>
        <div class="mt-4">
            <a href="../products/edit.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Product
            </a>
        </div>
        <?php endif; ?>
    </div>
</div> 