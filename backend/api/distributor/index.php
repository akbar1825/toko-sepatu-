<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

try {
    $query = "SELECT * FROM distributor ORDER BY id_distributor ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true, 
        "message" => "Data distributor berhasil ditampilkan",
        "data" => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode ([
        "status" => false,
        "massega" => "Gagal menampilkan data distributor"
    ]);
}