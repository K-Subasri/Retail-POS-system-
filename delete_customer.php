<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if(!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
        exit;
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Delete related sale items first
            $stmt = $db->prepare("DELETE FROM sale_items WHERE sale_id IN (SELECT sale_id FROM sales WHERE customer_id = :id)");
            $stmt->bindParam(':id', $customerId);
            $stmt->execute();
            
            // Delete related sales
            $stmt = $db->prepare("DELETE FROM sales WHERE customer_id = :id");
            $stmt->bindParam(':id', $customerId);
            $stmt->execute();
            
            // Now delete the customer
            $stmt = $db->prepare("DELETE FROM customers WHERE customer_id = :id");
            $stmt->bindParam(':id', $customerId);
            $stmt->execute();
            
            // Commit transaction
            $db->commit();
            echo json_encode(['success' => true]);
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $db->rollBack();
            throw $e;
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>