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

$kd_barang = filter_var($data["kd_barang"] ?? null, FILTER_VALIDATE_INT);
$qty_masuk = filter_var($data["qty_masuk"] ?? null, FILTER_VALIDATE_INT);
$harga_beli = $data["harga_beli"] ?? null;
$harga_jual = $data["harga_jual"] ?? null;

if (
    $kd_barang === false || $kd_barang === null ||
    $qty_masuk === false || $qty_masuk === null || $qty_masuk <= 0 ||
    !is_numeric($harga_beli) || $harga_beli < 0 ||
    !is_numeric($harga_jual) || $harga_jual < 0 
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data barang masuk tidak valid"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    //Pastikan produk ada dan kunci barisnya selama transaksi.
    $queryProduk = "SELECT id_produk, stok
                    FROM produk
                    WHERE id_produk = :kd_barang
                    FOR UPDATE";
    $stmtProduk = $pdo->prepare($queryProduk);
    $stmtProduk->execute([":kd_barang" => $kd_barang]);
    $produk = $stmtProduk->fetch(PDO::FETCH_ASSOC);

    if (!$produk) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            "status" => false,
            "message" => "Produk tidak ditemukan"
        ]);
        exit;
    }

    $queryStok = "INSERT INTO stok_produk
                    (tgl_masuk, kd_barang, harga_beli, harga_jual, qty_masuk)
                    VALUES
                    (NOW(), :kd_barang, :harga_beli, :harga_jual, :qty_masuk)";
    $stmtStok = $pdo->prepare($queryStok);
    $stmtStok->execute([
        ":kd_barang" => $kd_barang,
        ":harga_beli" => $harga_beli,
        ":harga_jual" => $harga_jual,
        ":qty_masuk" => $qty_masuk  
    ]);

    $id_stok_produk =$pdo->lastInsertId();

    $queryUpdate ="Update produk
                    SET Stok = COALESCE(stok, 0) + :qty_masuk
                    WHERE id_produk = :kd_barang";
    $stmtUpdate = $pdo->prepare($queryUpdate);
    $stmtUpdate->execute([
        ":qty_masuk" => $qty_masuk,
        ":kd_barang" => $kd_barang
    ]);

    $stok_baru = (int)($produk["stok"] ?? 0) + $qty_masuk;

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Barang masuk berhasil dicatat",
        "id_stok_produk" => $id_stok_produk,
        "kd_barang" => $kd_barang,
        "qty_masuk" => $qty_masuk,
        "stok_baru" => $stok_baru
    ]);

} catch (PDOException $e) {
    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal mencatat barang masuk"
    ]);
}