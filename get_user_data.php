<?php
include 'db_connection.php';

// Assuming you're passing userId via query parameter or token validation
$user_id = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
// In a real app, you'd validate a session token here to ensure the user is authorized.

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid User ID."]);
    exit();
}

$response_data = [
    "success" => false,
    "user" => null,
    "todayEarnings" => 0,
    "totalEarnings" => 0,
    "transactions" => [],
    "completedTasks" => 0
];

// Fetch user basic data
$sql_user = "SELECT id, username, balance, vip_level FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $response_data['user'] = $result_user->fetch_assoc();
    $response_data['success'] = true;
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "User not found."]);
    $stmt_user->close();
    $conn->close();
    exit();
}
$stmt_user->close();

// Calculate Today's Earnings (commissions from today)
$today = date('Y-m-d');
$sql_today_earnings = "SELECT SUM(amount) AS today_earnings FROM transactions WHERE user_id = ? AND type = 'commission' AND DATE(timestamp) = ? AND status = 'completed'";
$stmt_today_earnings = $conn->prepare($sql_today_earnings);
$stmt_today_earnings->bind_param("is", $user_id, $today);
$stmt_today_earnings->execute();
$result_today_earnings = $stmt_today_earnings->get_result();
$row_today_earnings = $result_today_earnings->fetch_assoc();
$response_data['todayEarnings'] = $row_today_earnings['today_earnings'] ?? 0;
$stmt_today_earnings->close();

// Calculate Total Earnings (all commissions)
$sql_total_earnings = "SELECT SUM(amount) AS total_earnings FROM transactions WHERE user_id = ? AND type = 'commission' AND status = 'completed'";
$stmt_total_earnings = $conn->prepare($sql_total_earnings);
$stmt_total_earnings->bind_param("i", $user_id);
$stmt_total_earnings->execute();
$result_total_earnings = $stmt_total_earnings->get_result();
$row_total_earnings = $result_total_earnings->fetch_assoc();
$response_data['totalEarnings'] = $row_total_earnings['total_earnings'] ?? 0;
$stmt_total_earnings->close();

// Fetch Transaction Log
$sql_transactions = "SELECT id, type, amount, description, timestamp, status FROM transactions WHERE user_id = ? ORDER BY timestamp DESC";
$stmt_transactions = $conn->prepare($sql_transactions);
$stmt_transactions->bind_param("i", $user_id);
$stmt_transactions->execute();
$result_transactions = $stmt_transactions->get_result();
while ($row = $result_transactions->fetch_assoc()) {
    $response_data['transactions'][] = $row;
}
$stmt_transactions->close();

// Count completed tasks (commissions)
$sql_completed_tasks = "SELECT COUNT(id) AS completed_tasks FROM transactions WHERE user_id = ? AND type = 'commission' AND status = 'completed'";
$stmt_completed_tasks = $conn->prepare($sql_completed_tasks);
$stmt_completed_tasks->bind_param("i", $user_id);
$stmt_completed_tasks->execute();
$result_completed_tasks = $stmt_completed_tasks->get_result();
$row_completed_tasks = $result_completed_tasks->fetch_assoc();
$response_data['completedTasks'] = $row_completed_tasks['completed_tasks'] ?? 0;
$stmt_completed_tasks->close();


echo json_encode($response_data);
$conn->close();
?>