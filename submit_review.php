<?php
include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"));

$user_id = isset($data->userId) ? intval($data->userId) : 0;
$product_id = isset($data->productId) ? intval($data->productId) : 0;
$rating = isset($data->rating) ? intval($data->rating) : 0;
$review_text = isset($data->review) ? $conn->real_escape_string($data->review) : NULL;
$commission_amount = isset($data->commissionAmount) ? floatval($data->commissionAmount) : 0.00;

if ($user_id <= 0 || $product_id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid review data provided."]);
    exit();
}

$conn->begin_transaction();

try {
    // 1. Record the review
    $sql_record_review = "INSERT INTO reviews (user_id, product_id, rating, review_text) VALUES (?, ?, ?, ?)";
    $stmt_review = $conn->prepare($sql_record_review);
    $stmt_review->bind_param("iiis", $user_id, $product_id, $rating, $review_text);
    $stmt_review->execute();
    $stmt_review->close();

    // 2. Update user's balance with commission
    $sql_update_balance = "UPDATE users SET balance = balance + ? WHERE id = ?";
    $stmt_balance = $conn->prepare($sql_update_balance);
    $stmt_balance->bind_param("di", $commission_amount, $user_id);
    $stmt_balance->execute();
    $stmt_balance->close();

    // 3. Record the commission transaction
    $transaction_type = 'commission';
    $description = "Review commission for product ID: " . $product_id;
    $status = 'completed'; // Commissions are completed instantly
    $sql_log_transaction = "INSERT INTO transactions (user_id, product_id, type, amount, description, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_log = $conn->prepare($sql_log_transaction);
    $stmt_log->bind_param("iissds", $user_id, $product_id, $transaction_type, $commission_amount, $description, $status);
    $stmt_log->execute();
    $stmt_log->close();
    
    $conn->commit();

    echo json_encode(["success" => true, "message" => "Review submitted and commission earned!", "new_balance" => get_user_balance($conn, $user_id)]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to submit review: " . $e->getMessage()]);
}

$conn->close();

function get_user_balance($conn, $userId) {
    $sql = "SELECT balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user['balance'];
}
?>