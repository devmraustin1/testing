<?php
include 'db_connection.php';
include 'admin_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'POST': // Create or Update Invite Code
        if (!isset($data['code']) || empty($data['code'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invite code is required."]);
            exit();
        }

        $code = $conn->real_escape_string($data['code']);
        $created_by_user_id = isset($data['created_by_user_id']) && $data['created_by_user_id'] !== '' ? intval($data['created_by_user_id']) : NULL;
        $max_uses = isset($data['max_uses']) ? intval($data['max_uses']) : 0;
        $bonus_amount = isset($data['bonus_amount']) ? floatval($data['bonus_amount']) : 0.00;
        $status = isset($data['status']) ? $conn->real_escape_string($data['status']) : 'active';

        // Check if code exists
        $check_sql = "SELECT code FROM invite_codes WHERE code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) { // Update existing
            $sql = "UPDATE invite_codes SET created_by_user_id=?, max_uses=?, bonus_amount=?, status=? WHERE code=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iidss", $created_by_user_id, $max_uses, $bonus_amount, $status, $code);
        } else { // Create new
            $sql = "INSERT INTO invite_codes (code, created_by_user_id, max_uses, bonus_amount, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siids", $code, $created_by_user_id, $max_uses, $bonus_amount, $status);
        }

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Invite code saved successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to save invite code: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE': // Delete Invite Code
        $code = isset($_GET['code']) ? $conn->real_escape_string($_GET['code']) : '';
        if (!empty($code)) {
            $sql = "DELETE FROM invite_codes WHERE code=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $code);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Invite code deleted successfully!"]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to delete invite code: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invite code is required for deletion."]);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}

$conn->close();
?>