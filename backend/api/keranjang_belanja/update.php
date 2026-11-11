<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Format JSON tidak valid"
    ]);
    exit;

}

$id_keranjang = filter_var(
    $data ["id_keranjang_belanja"] ?? null,
    FILTER_VALIDATE_INT
);

$kd_user = filter_var($data["kd_user"] ?? null, FILTER_VALIDATE_INT);
$qty = filter_var($data["qty"] ?? null, FILTER_VALIDATE_INT);

if (
    $id_keranjang === false || $id_keranjang === null || $id_keranjang <= 0 ||
    $kd_user === false || $kd_user === null || $kd_user <= 0 ||
    $qty === false || $qty === null || $qty <= 0
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data keranjang tidak valid"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();
    $query = "SELECT
                kb.id_keranjang_belanja,
                kb.kd_produk,
                p.nama_produk,
                p.harga_jual,
                p.stok
                FROM keranjang_belanja AS kb
                INNER JOIN produk AS p ON p.id_produk = kb.kd_produk
                WHERE kb.id_keranjang_belanja = :id_keranjang
                    AND kb.kd_user = :kd_user
                FOR UPDATE";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ":id_keranjang" => $id_keranjang,
        ":kd_user" => $kd_user
    ]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            "status" => false,
            "message" => "Item keranjang tidak ditemukan"
        ]);
        exit;
    }
    $stok_tersedia = (int) ($item["stok"] ?? 0);

    if ($qty > $stok_tersedia) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            "status" => false,
            "message" => "Stok produk tidak mencukupi",
            "stok_tersedia" => $stok_tersedia
        ]);
        exit;
    }
        $stmtUpdate = $pdo->prepare(
                "UPDATE keranjang_belanja
                 SET qty = :qty
                 WHERE id_keranjang_belanja = :id_keranjang
                     AND kd_user = :kd_user"
        );
        $stmtUpdate->execute([
        ":qty" => $qty,
        ":id_keranjang" => $id_keranjang,
        ":kd_user" => $kd_user
    ]);

    $pdo->commit();

    echo json_encode([
        "status" => true,
        "message" => "Jumlah keranjang berhasil diperbarui",
        "id_keranjang_belanja" => $id_keranjang,
        "kd_user" => $kd_user,
        "kd_produk" => (int)$item["kd_produk"],
        "qty" => $qty,
        "subtotal" => (float) $item["harga_jual"] * $qty
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal memperbarui jumlah keranjang"
    ]);
}