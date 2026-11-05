<?php
header("Content-Type: application/json");
require_once '../../config/database.php';

try {
    $query = "SELECT * FROM ukuran ORDER BY id_ukuran ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data ukuran berhasil ditampilkan",
        "ukuran" => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => false,
        "massage" => "Gagal menampilkan data ukuran"
    ]);
}