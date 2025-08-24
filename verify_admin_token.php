<?php
include 'db_connection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For this demonstration, we'll use a very basic mock based on session.
// In a real application, you would:
// 1. Look up the `token` in a `sessions` or `admin_tokens` table in your database.
// 2. Verify its validity (e.g., not expired).
// 3. Retrieve the `user_id` associated with the token.
// 4. Check if that user_id corresponds to an admin user (`is_admin = TRUE`).

$is_valid_admin_session = false;
$user_data = null;

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $sql = "SELECT id, username, balance, vip_level, is_admin FROM users WHERE id = ? AND is_admin = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $is_valid_admin_session = true;
    }
    $stmt->close();
}

if ($is_valid_admin_session) {
    echo json_encode(["success" => true, "message" => "Admin session is valid.", "is_admin" => true, "user" => $user_data]);
} else {
    echo json_encode(["success" => false, "message" => "No valid admin session.", "is_admin" => false]);
    // Clear any potentially stale admin session data
    unset($_SESSION['is_admin']);
    unset($_SESSION['user_id']);
}

$conn->close();
?>