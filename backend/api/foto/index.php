
<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

try {
    $query = "SELECT * FROM foto ORDER BY id_foto ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data foto berhasil ditampilkan",
        "foto" => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => "Gagal menampilkan data foto"
    ]);
}
