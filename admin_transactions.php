<?php
include 'db_connection.php';
include 'admin_auth.php'; // Ensure admin access

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'PUT': // Update Transaction Status
        $transaction_id = isset($data['id']) ? intval($data['id']) : 0;
        $status = isset($data['status']) ? $conn->real_escape_string($data['status']) : '';

        if ($transaction_id > 0 && !empty($status)) {
            $conn->begin_transaction();
            try {
                // Get current transaction details before updating
                $sql_get_current_trans = "SELECT user_id, type, amount, status FROM transactions WHERE id=?";
                $stmt_get_current_trans = $conn->prepare($sql_get_current_trans);
                $stmt_get_current_trans->bind_param("i", $transaction_id);
                $stmt_get_current_trans->execute();
                $result_get_current_trans = $stmt_get_current_trans->get_result();
                $current_transaction = $result_get_current_trans->fetch_assoc();
                $stmt_get_current_trans->close();

                if (!$current_transaction) {
                    throw new Exception("Transaction not found.");
                }

                // Only allow status change from 'pending'
                if ($current_transaction['status'] !== 'pending') {
                    throw new Exception("Transaction is not in 'pending' status. Cannot update.");
                }

                // 1. Update transaction status
                $sql_update_trans = "UPDATE transactions SET status=? WHERE id=?";
                $stmt_trans = $conn->prepare($sql_update_trans);
                $stmt_trans->bind_param("si", $status, $transaction_id);
                $stmt_trans->execute();
                $stmt_trans->close();

                // 2. If status is 'completed' or 'rejected', adjust user balance if needed
                if ($status === 'completed' || $status === 'rejected') {
                    $user_id = $current_transaction['user_id'];
                    $amount = $current_transaction['amount'];
                    $type = $current_transaction['type'];

                    $sql_update_balance = "";
                    // If deposit approved: add to balance
                    if ($type === 'deposit' && $status === 'completed') {
                        $sql_update_balance = "UPDATE users SET balance = balance + ? WHERE id=?";
                    } 
                    // If withdrawal approved: subtract from balance (should have been checked at withdrawal initiation, but this confirms)
                    else if ($type === 'withdrawal' && $status === 'completed') {
                        $sql_update_balance = "UPDATE users SET balance = balance - ? WHERE id=?";
                    }
                    // If deposit rejected: do nothing, it was never added
                    // If withdrawal rejected: add back to balance (it was previously deducted/held)
                    else if ($type === 'withdrawal' && $status === 'rejected') {
                        $sql_update_balance = "UPDATE users SET balance = balance + ? WHERE id=?";
                    }
                    // For commission transactions, balance is updated immediately on review submission,
                    // so no further balance change here for 'commission' type.

                    if (!empty($sql_update_balance)) {
                        $stmt_balance = $conn->prepare($sql_update_balance);
                        $stmt_balance->bind_param("di", $amount, $user_id);
                        $stmt_balance->execute();
                        $stmt_balance->close();
                    }
                }

                $conn->commit();
                echo json_encode(["success" => true, "message" => "Transaction status updated!"]);

            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to update transaction status: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid transaction ID or status."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}

$conn->close();
?>