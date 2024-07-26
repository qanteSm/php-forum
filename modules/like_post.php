<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "mysqlconn.php";

function kullaniciBegendiMi($conn, $postId, $userId) {
    $sql = "SELECT id FROM post_likes WHERE liker_id = ? AND liked_post = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    return ($result->num_rows > 0);
}

if (isset($_SESSION['entered']) && $_SESSION['entered'] == true && isset($_POST['post_id'])) { 
    $userId = $_SESSION['id'];
    $postId = (int)$_POST['post_id']; 

    if (kullaniciBegendiMi($conn, $postId, $userId)) {
        $sql = "DELETE FROM post_likes WHERE liker_id = ? AND liked_post = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $postId);
        $basarili = $stmt->execute();
    } else {
        $tarih = round(microtime(true) * 1000);
        $sql = "INSERT INTO post_likes (liker_id, liked_post, tarih) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $userId, $postId, $tarih);
        $basarili = $stmt->execute();

        $sql = "SELECT yazar, baslik FROM posts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $postYazarId = $row['yazar']; 
        $postBaslik = $row['baslik'];

        $sql = "SELECT username FROM accounts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId); 
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $kullaniciAdi = $row['username']; 

        $user_id = $postYazarId; 
        $type = "3"; 
        $content = "$kullaniciAdi adlı kullanıcı "; 

        if (strlen($postBaslik) > 20) {
        $postBaslik = substr($postBaslik, 0, 20) . "...";
        }
        $content .= "'$postBaslik' başlıklı postunuzu beğendi.";

        $link = "post.php?post=".$postId; 
        if ($userId != $user_id){

            $sql = "INSERT INTO notifications (user_id, maker_id, type, content, link) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisss", $user_id,$userId, $type, $content, $link);
            $basarilia = $stmt->execute();
        }
    }

    echo json_encode(['success' => $basarili]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Oturum açık değil veya post_id eksik.',
        'session' => $_SESSION, 
        'post' => $_POST        
    ]);
}

$conn->close();
?>