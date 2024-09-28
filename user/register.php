<?php
require 'db_config.php';
require '../vendor/autoload.php';
require 'validation.php';
require 'token.php';


function checkUserExist($userName)
{
    [$stmt, $result] = execQuery(
        "SELECT * FROM user WHERE name = ?",
        "s",
        $userName
    );
    $user_exists = $result->num_rows > 0;
    $stmt->close();
    // User exist check
    if ($user_exists) {
        http_response_code(400);
        echo json_encode(['error' => 'User already exists']);
        exit();
    }
}

function insertUser($userName, $password, $email)
{
    global $conn;
    $hashedPassword = hash('sha256', $password);
    [$stmt, $affected_rows] = execQuery(
        "INSERT INTO user (name, password, email) VALUES (?, ?, ?)",
        "sss",
        $userName,
        $hashedPassword,
        $email
    );
    $stmt->close();
    // insert rows
    if ($affected_rows <= 0) {
        http_response_code(500);
        echo json_encode(['error' => 'Faild to register user into database']);
        exit();
    }
}

function genResp($userName)
{
    [$stmt, $result] = execQuery(
        "SELECT * FROM user WHERE name = ?",
        "s",
        $userName
    );
    $user = $result->fetch_assoc();
    // Generate JWT token
    $jwt = generateToken($userName);
    echo json_encode(array("token" => $jwt, "user_name" => $userName, "user_id" => (int) $user['id'], "email" => $user['email']));
    $stmt->close();
}

$data = ENSURE_TOKEN_METHOD_ARGUMENT(['user_name', 'password', 'email']);
$userName = $data['user_name'];
$password = $data['password'];
$email = $data['email'];

if (!validateUserName($userName) || !validatePassword($password) || !validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

checkUserExist($userName);
insertUser($userName, $password, $email);
genResp($userName);

$conn->close();
?>