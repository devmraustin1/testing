<?php
include 'db_connection.php';
include 'admin_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'PUT': // Update System Settings
        if (!empty($data)) {
            $conn->begin_transaction();
            try {
                foreach ($data as $key => $value) {
                    $setting_key = $conn->real_escape_string($key);
                    $setting_value = $conn->real_escape_string($value);
                    $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $setting_key, $setting_value, $setting_value);
                    $stmt->execute();
                    $stmt->close();
                }
                $conn->commit();
                echo json_encode(["success" => true, "message" => "System settings updated successfully!"]);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to update settings: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No settings data provided."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}

$conn->close();
?>