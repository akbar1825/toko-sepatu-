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

$kd_user = filter_var($data["kd_user"] ?? null, FILTER_VALIDATE_INT);
$kd_produk = filter_var($data["kd_produk"] ?? null, FILTER_VALIDATE_INT);
$qty = filter_var($data["qty"] ?? null, FILTER_VALIDATE_INT);

if (
    $kd_user === false || $kd_user === null || $kd_user <= 0 ||
    $kd_produk === false || $kd_produk === null || $kd_produk <= 0 ||
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

    $stmtUser = $pdo->prepare(
        "SELECT id_user FROM `user` WHERE id_user = :kd_user"
    );
    $stmtUser->execute([":kd_user" => $kd_user]);

    if (!$stmtUser->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            "status" => false,
            "message" => "User tidak ditemukan"
        ]);
        exit;
    }
    $stmtProduk = $pdo->prepare(
        "SELECT id_produk, nama_produk, harga_jual, stok
        FROM produk
        WHERE id_produk = :kd_produk
        FOR UPDATE"
    );
    $stmtProduk->execute([":kd_produk" => $kd_produk]);
    $produk = $stmtProduk->fetch(PDO::FETCH_ASSOC);

    if (!$produk) {
        http_response_code(404);
        $pdo->rollBack();
        echo json_encode([
            "status" => false,
            "message" => "produk tidak ditemukan"
        ]);
        exit;
    }
    
    $stmtKeranjang = $pdo->prepare(
        "SELECT id_keranjang_belanja, qty
        FROM keranjang_belanja
        WHERE kd_user = :kd_user AND kd_produk = :kd_produk
        ORDER BY id_keranjang_belanja ASC
        LIMIT 1
        FOR UPDATE"
    );
    $stmtKeranjang->execute([
        ":kd_user" => $kd_user,
        ":kd_produk" => $kd_produk
    ]);
    $itemLama = $stmtKeranjang->fetch(PDO::FETCH_ASSOC);

    $qty_baru =(int) ($itemLama["qty"] ?? 0) + $qty;
    $stok_tersedia = (int) ($produk["stok"] ?? 0);

    if ($qty_baru > $stok_tersedia) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            "status" => false,
            "message" => "stok produk tidak mencukupi",
            "stok_tersedia" => $stok_tersedia
        ]);
        exit;
    }

    if ($itemLama) {
        $stmtUpdate = $pdo->prepare(
            "UPDATE keranjang_belanja
            SET qty = :qty, tgl_keranjang = NOW()
            WHERE id_keranjang_belanja = :id_keranjang"
        );
        $stmtUpdate->execute([
            ":qty" => $qty_baru,
            ":id_keranjang" => $itemLama["id_keranjang_belanja"]
        ]);
        $id_keranjang =$itemLama["id_keranjang_belanja"];
    } else {
        $stmtInsert = $pdo->prepare(
            "INSERT INTO keranjang_belanja
                (kd_user, tgl_keranjang, kd_produk, qty)
            VALUES
                (:kd_user, NOW(), :kd_produk, :qty)"
        );
        $stmtInsert->execute([
            ":kd_user" => $kd_user,
            ":kd_produk" => $kd_produk,
            ":qty" => $qty
        ]);

        $id_keranjang = $pdo->lastInsertId();
    }
    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Produk berhasil ditambahkan ke keranjang",
        "id_keranjang_belanja" => $id_keranjang,
        "kd_user" => $kd_user,
        "kd_produk" => $kd_produk,
        "qty" => $qty_baru,
        "subtotal" => (float)$produk["harga_jual"] * $qty_baru
    ]);
} catch (PDOException $e){
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal menambahkan produk ke karanjang"
    ]);
}