
<?php
header("Content-Type: application/json");

require_once "../../config/database.php";

try {
    $input = json_decode(file_get_contents("php://input"), true);

    $query = "INSERT INTO distributor
              (nm_distributor, alamat, no_telepon, email, nm_seles, hp_seles)
              VALUES
              (:nm_distributor, :alamat, :no_telepon, :email, :nm_seles, :hp_seles)";

    $stmt = $pdo->prepare($query);

    $stmt->execute([
        ":nm_distributor" => $input["nm_distributor"],
        ":alamat" => $input["alamat"],
        ":no_telepon" => $input["no_telepon"],
        ":email" => $input["email"],
        ":nm_seles" => $input["nm_seles"],
        ":hp_seles" => $input["hp_seles"]
    ]);

    echo json_encode([
        "status" => true,
        "message" => "Data distributor berhasil ditambahkan",
        "id_distributor" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => "Gagal menambahkan data distributor"
    ]);
}
