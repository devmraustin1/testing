<?php
// This file should be included at the beginning of every admin API script.
// It assumes a simple session-based auth for demonstration.
// In a production environment, implement robust session management or JWTs.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if an admin session is active
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(["success" => false, "message" => "Access denied. Admin privileges required."]);
    exit();
}
// Optionally, you might check an X-Auth-Token header if you're using JWTs or similar
// $headers = getallheaders();
// $authToken = isset($headers['X-Auth-Token']) ? $headers['X-Auth-Token'] : '';
// if (empty($authToken) || $authToken !== 'YOUR_VERY_SECRET_ADMIN_TOKEN') { ... } // (Example for token, but session is easier for this demo)
?>