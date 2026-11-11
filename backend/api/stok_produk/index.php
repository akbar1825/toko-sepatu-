<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

try {
    $query = "SELECT
                sp.id_stok_produk,
                sp.tgl_masuk,
                sp.kd_barang,
                p.nama_produk,
                sp.harga_beli,
                sp.harga_jual,
                sp.qty_masuk,
                p.stok AS stok_sekarang
                FROM stok_produk AS sp
                INNER JOIN produk AS p
                    ON p.id_produk = sp.kd_barang
                ORDER BY sp.id_stok_produk ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true, 
        "massega" => "Data stok produk berhasil ditampilkan",
        "stok_produk" => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => "Gagal menampilkan data stok produk"
    ]);
}