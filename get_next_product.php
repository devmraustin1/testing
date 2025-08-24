<?php
include 'db_connection.php';

// Assuming user_id and vip_level are passed to tailor product recommendations
$user_id = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
$vip_level = isset($_GET['vipLevel']) ? intval($_GET['vipLevel']) : 0;

// In a real application, you would add more sophisticated logic to:
// 1. Fetch a product that the user has not yet reviewed.
// 2. Consider the user's VIP level for exclusive products.
// 3. Ensure product is active.

// For now, let's fetch a random product suitable for the user's VIP level and not yet reviewed by them.
$sql = "SELECT p.id, p.name, p.price, p.commission_rate, p.image_url, p.description
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id AND r.user_id = ?
        WHERE r.id IS NULL AND p.is_active = TRUE AND p.min_vip_level <= ?
        ORDER BY RAND() LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $vip_level);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo json_encode(["success" => true, "product" => $product]);
} else {
    echo json_encode(["success" => false, "message" => "No new products available for review at your VIP level."]);
}

$stmt->close();
$conn->close();
?>