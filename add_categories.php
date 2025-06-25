<?php
require_once 'includes/db.php';

try {
    // Add Stationery category
    $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute(['Stationery', 'Office supplies and stationery items']);
    
    echo "Stationery category added successfully!";
} catch (PDOException $e) {
    echo "Error adding category: " . $e->getMessage();
}
?> 