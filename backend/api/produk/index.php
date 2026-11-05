<?php

header("Content-Type: application/json;");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-type");

require_once __DIR__ . "/../../config/database.php";


try {
    $query ="SELECT * FROM produk ORDER BY id_produk DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "true",
        "message"=> "Data Produk Berhasil Ditampilkan",
        "data" => $produk
        
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => "false",
        "message" => "Gagal Menampilkan Data Produk"
    ]);
}