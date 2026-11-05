<?php 
header("content-Type: application/json");
require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    !isset($data["nm_warna"]) ||
    !is_string($data["nm_warna"]) ||
    trim($data["nm_warna"]) == ""
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data warna tidak valid"
    ]);
    exit;
}

$nm_warna = trim($data["nm_warna"]);

try {
    $query = "INSERT INTO warna (nm_warna) VALUES (:nm_warna)";
    $stmt = $pdo->prepare($query);

    $stmt->execute([
        ":nm_warna" => $nm_warna
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal menambahkan data warna"
    ]);
}