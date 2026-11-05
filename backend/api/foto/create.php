
<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    !isset($data["nm_foto"], $data["path"]) ||
    !is_string($data["nm_foto"]) ||
    !is_string($data["path"]) ||
    trim($data["nm_foto"]) === "" ||
    trim($data["path"]) === ""
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data foto tidak valid"
    ]);
    exit;
}

$nm_foto = trim($data["nm_foto"]);
$path = trim($data["path"]);

try {
    $query = "INSERT INTO foto (nm_foto, path)
              VALUES (:nm_foto, :path)";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ":nm_foto" => $nm_foto,
        ":path" => $path
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "Data foto berhasil ditambahkan",
        "id_foto" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal menambahkan data foto"
    ]);
}
