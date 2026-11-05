<?php
header("content-Type: application/json");
require_once '../../config/database.php';

try {
    $query ="SELECT * FROM warna ORDER BY id_warna ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $data =$stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data warna berhasil ditampilkan",
        "warna" => $data
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Gagal menampilkan data warna"
    ]);
}