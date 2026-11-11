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
    $data["id_keranjang_belanja"] ?? null,
    FILTER_VALIDATE_INT
);
$kd_user = filter_var($data["kd_user"] ?? null, FILTER_VALIDATE_INT);

if (
    $id_keranjang === false || $id_keranjang === null || $id_keranjang <= 0 ||
    $kd_user === false || $kd_user === null || $kd_user <= 0
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data keranjang tidak valid"
    ]);
    exit;
}

try {
    $query = "DELETE FROM keranjang_belanja
              WHERE id_keranjang_belanja = :id_keranjang
              AND kd_user = :kd_user";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ":id_keranjang" => $id_keranjang,
        ":kd_user" => $kd_user
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => false,
            "message" => "Item keranjang tidak ditemukan"
        ]);
        exit;
    }

    echo json_encode([
        "status" => true,
        "message" => "Item berhasil dihapus dari keranjang",
        "id_keranjang_belanja" => $id_keranjang,
        "kd_user" => $kd_user
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal menghapus item dari keranjang",
        "debug" => $e->getMessage()
    ]);
}