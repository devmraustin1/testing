<?php
include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username and password are required."]);
    exit();
}

$username = $conn->real_escape_string($data->username);
$password = $conn->real_escape_string($data->password); // ⚠️ Hash this! e.g., password_hash($data->password, PASSWORD_DEFAULT)
$phone_number = isset($data->phoneNumber) ? $conn->real_escape_string($data->phoneNumber) : NULL;
$gender = isset($data->gender) ? $conn->real_escape_string($data->gender) : NULL;

// Check if username already exists
$check_sql = "SELECT id FROM users WHERE username = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "Username already taken."]);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

$sql = "INSERT INTO users (username, password, phone_number, gender) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $username, $password, $phone_number, $gender);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode(["success" => true, "message" => "Registration successful!"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Registration failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>