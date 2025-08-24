<?php
include 'db_connection.php'; // Include the database connection

// Include admin_auth.php if withdrawal processing requires admin privilege.
// For user withdrawals, you'd typically verify a user session/token instead.
// include 'admin_auth.php'; 

$data = json_decode(file_get_contents("php://input"));

// Basic input validation
if (!isset($data->userId) || !isset($data->amount) || !isset($data->address) || !isset($data->transactionPassword) || !isset($data->fee_percent)) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "All withdrawal details are required."]);
    exit();
}

$user_id = intval($data->userId);
$requested_amount = floatval($data->amount); // Amount user wants to withdraw (before fee)
$address = $conn->real_escape_string($data->address);
$transaction_password = $conn->real_escape_string($data->transactionPassword); // ⚠️ IMPORTANT: In a real app, do NOT send/store plaintext passwords! Hash/encrypt, or use a separate secure transaction PIN.
$fee_percent = floatval($data->fee_percent);

if ($requested_amount <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Withdrawal amount must be positive."]);
    exit();
}

// Start a transaction for atomicity
$conn->begin_transaction();

try {
    // 1. Verify user and their balance
    $sql_user = "SELECT id, balance FROM users WHERE id = ? AND password = ?"; // Basic password check for demo
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("is", $user_id, $transaction_password); // ⚠️ Secure password handling is crucial here
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows === 0) {
        throw new Exception("Authentication failed. Invalid user ID or transaction password.");
    }
    $user = $result_user->fetch_assoc();
    $stmt_user->close();

    $fee_amount = $requested_amount * ($fee_percent / 100);
    $final_deduction_amount = $requested_amount; // The amount to be deducted from user balance

    if ($user['balance'] < $final_deduction_amount) {
        throw new Exception("Insufficient balance to cover the withdrawal amount.");
    }

    // 2. Deduct amount from user's balance immediately
    $sql_deduct = "UPDATE users SET balance = balance - ? WHERE id = ?";
    $stmt_deduct = $conn->prepare($sql_deduct);
    $stmt_deduct->bind_param("di", $final_deduction_amount, $user_id);
    $stmt_deduct->execute();
    $stmt_deduct->close();

    // 3. Log the withdrawal transaction with 'pending' status
    $description = "Withdrawal to " . $address . " (Fee: " . ($fee_percent) . "%)";
    $status = 'pending'; // Withdrawals are typically pending admin approval

    $sql_log_trans = "INSERT INTO transactions (user_id, type, amount, description, status) VALUES (?, ?, ?, ?, ?)";
    $stmt_log_trans = $conn->prepare($sql_log_trans);
    $type = 'withdrawal';
    $stmt_log_trans->bind_param("isdss", $user_id, $type, $requested_amount, $description, $status);
    $stmt_log_trans->execute();
    $stmt_log_trans->close();

    // Commit the transaction
    $conn->commit();

    // Fetch updated balance to send back to frontend
    $sql_new_balance = "SELECT balance FROM users WHERE id = ?";
    $stmt_new_balance = $conn->prepare($sql_new_balance);
    $stmt_new_balance->bind_param("i", $user_id);
    $stmt_new_balance->execute();
    $result_new_balance = $stmt_new_balance->get_result();
    $new_balance = $result_new_balance->fetch_assoc()['balance'];
    $stmt_new_balance->close();

    echo json_encode([
        "success" => true,
        "message" => "Withdrawal request submitted successfully.",
        "new_balance" => $new_balance
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Withdrawal failed: " . $e->getMessage()]);
}

$conn->close();
?>
