<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../../config/database.php";

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data["nm_brand"]) ||
        !is_string($data["nm_brand"]) ||
        trim($data["nm_brand"]) === ""
    ){
        http_response_code(400);
        echo json_encode([
            "status" => false,
            "message" => "Nama Brand wajib diisi"
        ]);
        exit();
    }

    $nm_brand = trim($data["nm_brand"]);
    $keterangan = isset($data["keterangan"])
        ? trim($data["keterangan"])
        : null;

    $query = "INSERT INTO brand (nm_brand, keterangan)
              VALUES (:nm_brand, :keterangan)";

    $stmt = $pdo->prepare($query);

    $stmt->execute([
        ":nm_brand" => $nm_brand,
        ":keterangan" => $keterangan
    ]);

    http_response_code(201);

    echo json_encode([
        "status" => true,
        "message" => "Data Brand Berhasil Ditambahkan"
    ]);

   
} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => "Gagal Menambahkan Data Brand"
    ]);
}