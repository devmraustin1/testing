<?php
include 'db_connection.php'; // Include the database connection

$data = json_decode(file_get_contents("php://input")); // Get JSON data from frontend

if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Username and password are required."]);
    exit();
}

$username = $conn->real_escape_string($data->username);
$password = $conn->real_escape_string($data->password); // ⚠️ Remember to hash passwords in a real app!

$sql = "SELECT id, username, phone_number, gender, balance, vip_level, is_admin FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password); // "ss" means two string parameters
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Simulate a session token (for demonstration). In production, use JWT or proper session management.
    $session_token = bin2hex(random_bytes(16)); // Generate a random token
    // You might store this token in a server-side session or database with expiry.
    
    // Start session for simple admin auth check
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if ($user['is_admin']) {
        $_SESSION['is_admin'] = true;
        $_SESSION['user_id'] = $user['id']; // Store admin user ID
    } else {
        $_SESSION['is_admin'] = false;
        $_SESSION['user_id'] = $user['id']; // Store regular user ID
    }

    echo json_encode([
        "success" => true,
        "message" => "Login successful!",
        "user" => $user,
        "token" => $session_token // Send token to frontend
    ]);
} else {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "message" => "Invalid username or password."]);
}

$stmt->close();
$conn->close();
?>