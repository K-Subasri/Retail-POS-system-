<?php
require_once 'includes/db.php';

try {
    // Remove Electronics and Stationery categories
    $stmt = $db->prepare("DELETE FROM categories WHERE name IN (?, ?)");
    $stmt->execute(['Electronics', 'Stationery']);
    
    $count = $stmt->rowCount();
    echo "Successfully removed $count categories.";
} catch (PDOException $e) {
    echo "Error removing categories: " . $e->getMessage();
}
?> 