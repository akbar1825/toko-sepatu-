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
                kb.kd_produk,
                kb.qty AS qty_diminta,
                p.nama_produk,
                p.harga_jual,
                p.stok
            FROM keranjang_belanja AS kb
            INNER JOIN produk AS p
                ON p.id_produk = kb.kd_produk
            WHERE kb.kd_user = :kd_user
            ORDER BY kb.id_keranjang_belanja ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([":kd_user" => $kd_user]);
    $keranjang = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($keranjang) === 0) {
        echo json_encode([
            "status" => true,
            "requires_confirmation" => false,
            "message" => "Keranjang masih kosong",
            "item" => [],
            "total_disarankan" => 0 
        ]);
        exit;
    }
    
    $items = [];
    $total_disarankan = 0;
    $ada_penyesuaian = false;

    foreach ($keranjang as $item) {
        $qty_diminta = (int)$item["qty_diminta"];
        $stok_tersedia = (int)($item["stok"] ?? 0);
        $qty_disarankan = min($qty_diminta, $stok_tersedia);
        $harga_jual = (float)$item["harga_jual"];
        $subtotal_disarankan = $harga_jual * $qty_disarankan;

        if ($qty_disarankan < $qty_diminta) {
            $ada_penyesuaian = true;
        }

        $total_disarankan += $subtotal_disarankan;

        $items[] = [
            "id_keranjang_belanja" => (int)$item["id_keranjang_belanja"],
            "kd_produk" => (int)$item["kd_produk"],
            "nama_produk" => $item["nama_produk"],
            "qty_diminta" => $qty_diminta,
            "stok_tersedia" => $stok_tersedia,
            "qty_disarankan" => $qty_disarankan,
            "butuh_penyesuaian" => $qty_disarankan < $qty_diminta,
            "harga_jual" => $harga_jual,
            "subtotal_disarankan" => $subtotal_disarankan
        ];
    }
    echo json_encode([
        "status" => true,
        "requires_confirmation" => true,
        "message" => $ada_penyesuaian
            ? "Ada jumlah barang yang perlu disesuikan. Minta konfirmasi customer."
            : "Semua barang tersedia. Minta konfirmasi customer untuk melanjutkan.",
        "items" => $items,
        "total_disarankan" => $total_disarankan
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal memeriksa stok checkout"
    ]);
}