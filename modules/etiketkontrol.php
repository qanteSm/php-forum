<?php
header('Content-Type: application/json');

require_once "mysqlconn.php";

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = ['success' => false];

if ($userId > 0) {
    $sql = "SELECT username FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response['success'] = true;
        $response['username'] = $row['username'];
    }
}

echo json_encode($response);
?> 