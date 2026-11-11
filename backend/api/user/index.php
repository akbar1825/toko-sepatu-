<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

try {
    // Jangan kirim password, NIK, atau foto KTP dalam daftar umum.
    $query = "SELECT id_user, username, email, no_telepon, alamat
    FROM `user`
    ORDER BY id_user ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data user berhasil ditampilkan",
        "user" => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => "Gagal menampilkan data user"
    ]);
}