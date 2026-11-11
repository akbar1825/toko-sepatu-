<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

try {
    $query = "SELECT
                id_produk,
                nama_produk,
                harga_jual,
                deskripsi,
                stok
              FROM produk
              ORDER BY id_produk DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data produk berhasil ditampilkan",
        "data" => $produk
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => "Gagal menampilkan data produk"
    ]);
}
