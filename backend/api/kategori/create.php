<?php
header("Content-Type: application/json");
require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    !isset($data["nm_kategori"]) ||
    !is_string($data["nm_kategori"]) ||
    trim ($data["nm_kategori"]) === ""
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Data kategori tidak valid"
    ]);
    exit;
}

$nm_kategori = trim($data["nm_kategori"]);
$keterangan= isset($data["keterangan"]) &&
is_string($data["keterangan"])
? trim($data["keterangan"])
: null; 

try{
    $query = "INSERT INTO kategori (nm_kategori, keterangan) 
             VALUES (:nm_kategori, :keterangan)";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ":nm_kategori" => $nm_kategori,
        ":keterangan" => $keterangan
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message"=> "Data kategori berhasil ditambahkan",
        "id_kategori" =>$pdo->lastInsertId()
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal menambahkan data kategori"
    ]);
}