<?php
include 'db_connection.php';
include 'admin_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'POST': // Create or Update Product
        if (isset($data['id']) && $data['id'] > 0) { // Update existing product
            $sql = "UPDATE products SET name=?, description=?, price=?, commission_rate=?, image_url=?, min_vip_level=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddisi", $data['name'], $data['description'], $data['price'], $data['commissionRate'], $data['image'], $data['vipLevel'], $data['id']);
        } else { // Create new product
            $sql = "INSERT INTO products (name, description, price, commission_rate, image_url, min_vip_level) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddsi", $data['name'], $data['description'], $data['price'], $data['commissionRate'], $data['image'], $data['vipLevel']);
        }
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Product saved successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to save product: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE': // Delete Product
        $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($product_id > 0) {
            $sql = "DELETE FROM products WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Product deleted successfully!"]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to delete product: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid Product ID for deletion."]);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}

$conn->close();
?>