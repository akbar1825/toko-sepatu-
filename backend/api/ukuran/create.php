
<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    !isset($data["nm_ukuran"]) ||
    !is_string($data["nm_ukuran"]) ||
    trim($data["nm_ukuran"]) === ""
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data ukuran tidak valid"
    ]);
    exit;
}

$nm_ukuran = trim($data["nm_ukuran"]);
$keterangan = isset($data["keterangan"]) &&
    is_string($data["keterangan"])
    ? trim($data["keterangan"])
    : null;

try {
    $query = "INSERT INTO ukuran (nm_ukuran, keterangan)
              VALUES (:nm_ukuran, :keterangan)";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ":nm_ukuran" => $nm_ukuran,
        ":keterangan" => $keterangan
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Data ukuran berhasil ditambahkan",
        "id_ukuran" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal menambahkan data ukuran"
    ]);
}
