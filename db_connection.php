<?php
// Allow requests from any origin (for development). In production, restrict to your frontend domain.
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Auth-Token");

// Handle OPTIONS requests (preflight checks for CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ⚠️ YOUR HOSTINGER DATABASE CREDENTIALS ARE USED HERE ⚠️
$servername = "localhost";
$username = "u225136555_LxTPK";
$password = "d+14&HV$FbS";
$dbname = "u225136555_buWEx";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}
?>