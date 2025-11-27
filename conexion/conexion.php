<?php
// --- MySQLi (para controladores viejos) ---
$conn = @new mysqli("127.0.0.1", "root", "", "ut_db");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error de conexión (MySQLi): " . $conn->connect_error
    ]);
    exit;
}

// --- PDO (para controladores nuevos) ---
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=ut_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error de conexión (PDO): " . $e->getMessage()
    ]);
    exit;
}
?>