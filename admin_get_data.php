<?php
include 'db_connection.php';
include 'admin_auth.php'; // Ensure admin access

$response = [
    "success" => true,
    "users" => [],
    "products" => [],
    "allTransactions" => [],
    "inviteCodes" => [],
    "commissions" => [],
    "systemSettings" => [],
    "vipPlans" => [],
    "stats" => [
        "totalUsers" => 0,
        "activeUsers" => 0,
        "totalDeposits" => 0,
        "totalWithdrawals" => 0,
        "systemBalance" => 0
    ]
];

// Fetch Users
$sql_users = "SELECT id, username, balance, vip_level, is_admin, join_date FROM users";
$result_users = $conn->query($sql_users);
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $response['users'][] = $row;
    }
}

// Fetch Products
$sql_products = "SELECT id, name, description, price, commission_rate, image_url, min_vip_level FROM products";
$result_products = $conn->query($sql_products);
if ($result_products) {
    while ($row = $result_products->fetch_assoc()) {
        $response['products'][] = $row;
    }
}

// Fetch Transactions
$sql_transactions = "SELECT t.id, t.user_id, u.username, t.type, t.amount, t.description, t.timestamp, t.status FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.timestamp DESC";
$result_transactions = $conn->query($sql_transactions);
if ($result_transactions) {
    while ($row = $result_transactions->fetch_assoc()) {
        $response['allTransactions'][] = $row;
    }
}

// Fetch Invite Codes
$sql_invites = "SELECT code, created_by_user_id, max_uses, current_uses, bonus_amount, status, created_at FROM invite_codes";
$result_invites = $conn->query($sql_invites);
if ($result_invites) {
    while ($row = $result_invites->fetch_assoc()) {
        $response['inviteCodes'][] = $row;
    }
}

// Fetch Commissions (transactions of type 'commission')
$sql_commissions = "SELECT t.id, t.user_id, u.username, p.name as product_name, t.amount, p.commission_rate, t.timestamp FROM transactions t JOIN users u ON t.user_id = u.id LEFT JOIN products p ON t.product_id = p.id WHERE t.type = 'commission' ORDER BY t.timestamp DESC";
$result_commissions = $conn->query($sql_commissions);
if ($result_commissions) {
    while ($row = $result_commissions->fetch_assoc()) {
        $response['commissions'][] = $row;
    }
}

// Fetch System Settings
$sql_settings = "SELECT setting_key, setting_value FROM system_settings";
$result_settings = $conn->query($sql_settings);
if ($result_settings) {
    while ($row = $result_settings->fetch_assoc()) {
        $response['systemSettings'][$row['setting_key']] = $row['setting_value'];
    }
}

// Fetch VIP Plans
$sql_vip_plans = "SELECT level, level_name, min_deposit_range, max_deposit_range, commission_rate, tasks_per_group, daily_task_limit, lucky_order_reward, deposit_return_event FROM vip_plans ORDER BY level ASC";
$result_vip_plans = $conn->query($sql_vip_plans);
if ($result_vip_plans) {
    while ($row = $result_vip_plans->fetch_assoc()) {
        $response['vipPlans'][] = $row;
    }
}

// Calculate Statistics
$response['stats']['totalUsers'] = count($response['users']);
$response['stats']['activeUsers'] = count(array_filter($response['users'], function($user) {
    // Assuming 'status' is not yet in users table, default all fetched as active
    // If you add a 'status' column (e.g., active/suspended), filter by that.
    return true;
}));

$sql_total_deposits = "SELECT SUM(amount) AS total FROM transactions WHERE type = 'deposit' AND status = 'completed'";
$result_total_deposits = $conn->query($sql_total_deposits);
$response['stats']['totalDeposits'] = $result_total_deposits->fetch_assoc()['total'] ?? 0;

$sql_total_withdrawals = "SELECT SUM(amount) AS total FROM transactions WHERE type = 'withdrawal' AND status = 'completed'";
$result_total_withdrawals = $conn->query($sql_total_withdrawals);
$response['stats']['totalWithdrawals'] = $result_total_withdrawals->fetch_assoc()['total'] ?? 0;

$response['stats']['systemBalance'] = $response['stats']['totalDeposits'] - $response['stats']['totalWithdrawals'];


echo json_encode($response);
$conn->close();
?>