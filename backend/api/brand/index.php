<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../../config/database.php";

try {
    $query = "SELECT * FROM brand ORDER BY id_brand DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $brand = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data Brand Berhasil Ditampilkan",
        "brand" => $brand
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    
    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}