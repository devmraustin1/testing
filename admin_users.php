<?php
include 'db_connection.php';
include 'admin_auth.php'; // Ensure admin access

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true); // true for associative array

switch ($method) {
    case 'POST': // Create or Update User
        if (!isset($data['username'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Username is required."]);
            exit();
        }

        $username = $conn->real_escape_string($data['username']);
        $balance = isset($data['balance']) ? floatval($data['balance']) : 0.00;
        $vip_level = isset($data['vip_level']) ? intval($data['vip_level']) : 0;
        $is_admin = isset($data['is_admin']) ? ($data['is_admin'] ? 1 : 0) : 0;
        $password = isset($data['password']) && !empty($data['password']) ? $conn->real_escape_string($data['password']) : NULL;
        // ⚠️ In a real application, hash the password: $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if (isset($data['id']) && $data['id'] > 0) { // Update existing user
            $user_id = intval($data['id']);
            $updates = [];
            $bind_types = "";
            $bind_params = [];

            $updates[] = "username=?"; $bind_types .= "s"; $bind_params[] = $username;
            if ($password !== NULL) { $updates[] = "password=?"; $bind_types .= "s"; $bind_params[] = $password; } // Use $hashed_password
            $updates[] = "balance=?"; $bind_types .= "d"; $bind_params[] = $balance;
            $updates[] = "vip_level=?"; $bind_types .= "i"; $bind_params[] = $vip_level;
            $updates[] = "is_admin=?"; $bind_types .= "i"; $bind_params[] = $is_admin;
            
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id=?";
            $bind_types .= "i";
            $bind_params[] = $user_id;

            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
                exit();
            }
            $stmt->bind_param($bind_types, ...$bind_params);
        } else { // Create new user
            if ($password === NULL) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Password is required for new user."]);
                exit();
            }
            // Check if username already exists
            $check_sql = "SELECT id FROM users WHERE username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                http_response_code(409); // Conflict
                echo json_encode(["success" => false, "message" => "Username already exists."]);
                $check_stmt->close();
                $conn->close();
                exit();
            }
            $check_stmt->close();

            $sql = "INSERT INTO users (username, password, balance, vip_level, is_admin) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddi", $username, $password, $balance, $vip_level, $is_admin); // Use $hashed_password
        }
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "User saved successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to save user: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE': // Delete User
        $user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($user_id > 0) {
            // Before deleting user, delete associated transactions and reviews to maintain referential integrity
            $conn->begin_transaction();
            try {
                $stmt_trans = $conn->prepare("DELETE FROM transactions WHERE user_id=?");
                $stmt_trans->bind_param("i", $user_id);
                $stmt_trans->execute();
                $stmt_trans->close();

                $stmt_reviews = $conn->prepare("DELETE FROM reviews WHERE user_id=?");
                $stmt_reviews->bind_param("i", $user_id);
                $stmt_reviews->execute();
                $stmt_reviews->close();

                $sql = "DELETE FROM users WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $conn->commit();
                    echo json_encode(["success" => true, "message" => "User deleted successfully!"]);
                } else {
                    throw new Exception("Failed to delete user: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(["success" => false, "message" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid User ID for deletion."]);
        }
        break;
    
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}

$conn->close();
?>