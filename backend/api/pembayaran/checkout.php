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

if (
    $kd_user === false || $kd_user === null || $kd_user <= 0 ||
    ($data["confirmed"] ?? false) !== true ||
    !isset($data["items"]) || !is_array($data["items"]) ||
    count($data["items"]) === 0 
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data konfirmasi checkout tidak valid",
        "debug" => [
            "kd_user_diterima" => $data["kd_user"] ?? null,
            "kd_user_valid" => $kd_user,
            "confirmed_diterima" => $data["confirmed"] ?? null,
            "tipe_confirmed" => gettype($data["confirmed"] ?? null),
            "tipe_items" => gettype($data["items"] ?? null),
            "jumlah_items" => is_array($data["items"] ?? null)
                ? count($data["items"])
                : null
        ]
    ]);
    exit;
}

$pilihan = [];
$cartIds = [];

foreach ($data["items"] as $item) {
    if (!is_array($item)) {
        http_response_code(400);
        echo json_encode([
            "status" => false,
            "message" => "Item checkout tidak valid"
        ]);
        exit;
    }

    $id_keranjang = filter_var(
        $item["id_keranjang_belanja"] ?? null, 
        FILTER_VALIDATE_INT
    );

    $qty = filter_var($item["qty"] ?? null, FILTER_VALIDATE_INT);

    if(
        $id_keranjang === false || $id_keranjang === null || $id_keranjang <= 0 ||
        $qty === false || $qty === null || $qty <= 0 ||
        isset($cartIds[$id_keranjang])
    ) {
        http_response_code(400);
        echo json_encode([
            "status" => false,
            "message" => "ID keranjang atau jumlah barang tidak valid"
        ]);
        exit;
    }

    $cartIds[$id_keranjang] = true;
    $pilihan[] = [
        "id_keranjang_belanja" => $id_keranjang,
        "qty" => $qty
    ];
}
try {
    $pdo->beginTransaction();

    // Kunci dan periksa ulang setiap item serta stoknya.
    $stmtCek = $pdo->prepare(
        "SELECT
            kb.id_keranjang_belanja,
            kb.kd_produk,
            kb.qty AS qty_keranjang,
            p.nama_produk,
            p.harga_jual,
            p.harga_beli,
            p.stok
         FROM keranjang_belanja AS kb
         INNER JOIN produk AS p ON p.id_produk = kb.kd_produk
         WHERE kb.id_keranjang_belanja = :id_keranjang
           AND kb.kd_user = :kd_user
         FOR UPDATE"
    );

    $itemsCheckout = [];

    foreach ($pilihan as $pilih) {
        $stmtCek->execute([
            ":id_keranjang" => $pilih["id_keranjang_belanja"],
            ":kd_user" => $kd_user
        ]);

        $itemDB = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if (!$itemDB) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode([
                "status" => false,
                "message" => "Item keranjang tidak ditemukan"
            ]);
            exit;
        }

        $qtyKeranjang = (int)$itemDB["qty_keranjang"];
        $stokTersedia = (int)($itemDB["stok"] ?? 0);
        $qtyBeli = $pilih["qty"];

        if ($qtyBeli > $qtyKeranjang || $qtyBeli > $stokTersedia) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode([
                "status" => false,
                "message" => "Stok berubah atau jumlah yang dipilih melebihi keranjang. Periksa preview checkout lagi.",
                "kd_produk" => (int)$itemDB["kd_produk"],
                "qty_diminta" => $qtyBeli,
                "stok_tersedia" => $stokTersedia
            ]);
            exit;
        }

        $itemDB["qty_beli"] = $qtyBeli;
        $itemDB["qty_keranjang"] = $qtyKeranjang;
        $itemsCheckout[] = $itemDB;
    }

    // Pembayaran berstatus Berhasil untuk simulasi proyek.
    $stmtPembayaran = $pdo->prepare(
        "INSERT INTO pembayaran (invoice, tgl_bayar, status_pembayaran)
         VALUES (NULL, NOW(), 'Berhasil')"
    );
    $stmtPembayaran->execute();

    $id_pembayaran = (int)$pdo->lastInsertId();
    $invoice = str_pad((string)$id_pembayaran, 6, "0", STR_PAD_LEFT);

    $stmtInvoice = $pdo->prepare(
        "UPDATE pembayaran
         SET invoice = :invoice
         WHERE id_pembayaran = :id_pembayaran"
    );
    $stmtInvoice->execute([
        ":invoice" => $invoice,
        ":id_pembayaran" => $id_pembayaran
    ]);

    $stmtDetail = $pdo->prepare(
        "INSERT INTO detail_pembayaran
            (kd_pembayaran, kd_produk, harga_jual, harga_beli, qty)
         VALUES
            (:kd_pembayaran, :kd_produk, :harga_jual, :harga_beli, :qty)"
    );

    $stmtKurangiStok = $pdo->prepare(
        "UPDATE produk
         SET stok = stok - :qty
         WHERE id_produk = :kd_produk
           AND stok >= :qty"
    );

    $stmtHapusKeranjang = $pdo->prepare(
        "DELETE FROM keranjang_belanja
         WHERE id_keranjang_belanja = :id_keranjang
           AND kd_user = :kd_user"
    );

    $stmtSisaKeranjang = $pdo->prepare(
        "UPDATE keranjang_belanja
         SET qty = :qty
         WHERE id_keranjang_belanja = :id_keranjang
           AND kd_user = :kd_user"
    );

    $total = 0;
    $detailBerhasil = [];

    foreach ($itemsCheckout as $item) {
        $qtyBeli = (int)$item["qty_beli"];
        $hargaJual = (float)$item["harga_jual"];
        $hargaBeli = (float)$item["harga_beli"];
        $subtotal = $hargaJual * $qtyBeli;

        $stmtDetail->execute([
            ":kd_pembayaran" => $id_pembayaran,
            ":kd_produk" => $item["kd_produk"],
            ":harga_jual" => $hargaJual,
            ":harga_beli" => $hargaBeli,
            ":qty" => $qtyBeli
        ]);

        $stmtKurangiStok->execute([
            ":qty" => $qtyBeli,
            ":kd_produk" => $item["kd_produk"]
        ]);

        if ($stmtKurangiStok->rowCount() !== 1) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode([
                "status" => false,
                "message" => "Stok berubah. Periksa preview checkout lagi."
            ]);
            exit;
        }

        $qtySisa = (int)$item["qty_keranjang"] - $qtyBeli;

        if ($qtySisa === 0) {
            $stmtHapusKeranjang->execute([
                ":id_keranjang" => $item["id_keranjang_belanja"],
                ":kd_user" => $kd_user
            ]);
        } else {
            $stmtSisaKeranjang->execute([
                ":qty" => $qtySisa,
                ":id_keranjang" => $item["id_keranjang_belanja"],
                ":kd_user" => $kd_user
            ]);
        }

        $total += $subtotal;
        $detailBerhasil[] = [
            "kd_produk" => (int)$item["kd_produk"],
            "nama_produk" => $item["nama_produk"],
            "qty" => $qtyBeli,
            "subtotal" => $subtotal
        ];
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Checkout berhasil",
        "id_pembayaran" => $id_pembayaran,
        "invoice" => $invoice,
        "status_pembayaran" => "Berhasil",
        "items" => $detailBerhasil,
        "total" => $total
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal memproses checkout"
    ]);
}