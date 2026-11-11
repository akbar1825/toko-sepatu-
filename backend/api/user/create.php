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

$username = $data["username"] ?? null;
$password = $data["password"] ?? null;
$email = $data["email"] ?? null;

if (
    !is_string($username) ||
    trim($username) === "" ||
    strlen(trim($username)) > 255 ||
    !is_string($password) ||
    strlen($password) < 8 ||
    strlen($password) > 225
) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Username atau password tidak valid"
    ]);
    exit;

}

$username = trim($username);

if ($email !== null) {
    if (!is_string($email)) {
        echo json_encode([
            "status" => false,
            "message" => "Email tidak valid"
        ]);
        exit;

    }

    $email = trim($email);
    if ($email === "") {
        $email = null;
    } elseif (
        strlen($email) > 225 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        http_response_code(400);
        echo json_encode([
            "status" => false,
            "message" => "Email tidak valid"
        ]);
        exit;
    }
}

try {
    $stmtCek =$pdo->prepare(
        "SELECT id_user
        FROM `user`
        WHERE username = :username
        LIMIT 1"
    );
    $stmtCek->execute([":username" => $username]);

    if ($stmtCek->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409);
        echo json_encode([
            "status" => false,
            "message" => "Username sudah digunakan"
        ]);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO `user` (username, password, email)
              VALUES (:username, :password, :email)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ":username" => $username,
        ":password" => $password_hash,
        ":email" => $email
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "message" => "User berhasil dibuat",
        "username" => $username
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Gagal membuat user"
    ]);
}