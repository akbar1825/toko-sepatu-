<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

$kd_user = filter_var(
    $_GET["kd_user"] ?? null, 
    FILTER_VALIDATE_INT
);

if ($kd_user === false || $kd_user === null || $kd_user <= 0) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "kd_user tidak valid"
    ]);
    exit;
}

try {
    $query = "SELECT 
                kb.id_keranjang_belanja,
                kb.kd_user,
                kb.kd_produk,
                p.nama_produk,
                p.harga_jual,
                kb.qty,
                p.stok AS stok_tersedia,
                (p.harga_jual * kb.qty) AS subtotal
            FROM keranjang_belanja AS kb
            LEFT JOIN produk AS p 
                ON p.id_produk = kb.kd_produk
            WHERE kb.kd_user = :kd_user
            ORDER BY kb.id_keranjang_belanja ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([":kd_user" => $kd_user]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data keranjang berhasil ditampilkan",
        "keranjang_belanja" => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal menampilkan data keranjang"
    ]);
}