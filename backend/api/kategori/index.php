<?php
header("Content-Type: application/json");
require_once '../../config/database.php';

try{
    $query = "SELECT * FROM kategori ORDER BY id_kategori ASC";
    $stmt =$pdo->prepare($query);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "message" => "Data kategori berhasil ditampilkan",
        "kategori" => $data
    ]);

} catch(PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Gagal Menampilkan Data kategori" 
    ]);
}