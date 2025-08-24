<?php
include 'db_connection.php';
include 'admin_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'PUT': // Update VIP Plans
        if (isset($data['vip_plans']) && is_array($data['vip_plans'])) {
            $conn->begin_transaction();
            try {
                foreach ($data['vip_plans'] as $plan) {
                    $level = intval($plan['level']);
                    $level_name = $conn->real_escape_string($plan['level_name']);
                    $min_deposit_range = floatval($plan['min_deposit_range']);
                    $max_deposit_range = floatval($plan['max_deposit_range']);
                    $commission_rate = floatval($plan['commission_rate']); // Already converted from display % to decimal
                    $tasks_per_group = intval($plan['tasks_per_group']);
                    $daily_task_limit = intval($plan['daily_task_limit']);
                    $lucky_order_reward = floatval($plan['lucky_order_reward']); // Already converted from display % to decimal
                    $deposit_return_event = intval($plan['deposit_return_event']); // Converted to 0 or 1

                    $sql = "INSERT INTO vip_plans (level, level_name, min_deposit_range, max_deposit_range, commission_rate, tasks_per_group, daily_task_limit, lucky_order_reward, deposit_return_event)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            level_name=?, min_deposit_range=?, max_deposit_range=?, commission_rate=?, tasks_per_group=?, daily_task_limit=?, lucky_order_reward=?, deposit_return_event=?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("isddddiiisddddiiii", 
                        $level, $level_name, $min_deposit_range, $max_deposit_range, $commission_rate, $tasks_per_group, $daily_task_limit, $lucky_order_reward, $deposit_return_event,
                        $level_name, $min_deposit_range, $max_deposit_range, $commission_rate, $tasks_per_group, $daily_task_limit, $lucky_order_reward, $deposit_return_event
                    );
                    $stmt->execute();
                    $stmt->close();
                }
                $conn->commit();
                echo json_encode(["success" => true, "message" => "VIP plans updated successfully!"]);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to update VIP plans: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No VIP plan data provided."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}

$conn->close();
?>