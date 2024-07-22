<?php
session_start();

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    require_once "mysqlconn.php";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postId = $_POST['post_id'];
        $yorum = htmlspecialchars($_POST['yorum']); 

        if (empty($yorum)) {
            die("Hata: Yorum metni boş olamaz.");
        }

        if ($conn->connect_error) {
            die("Bağlantı hatası: " . $conn->connect_error);
        }

        $sql = "INSERT INTO replys (post_id, kullanici_id, yorum_metni, yorum_tarihi) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $postId, $_SESSION['id'], $yorum); 

        if ($stmt->execute()) {
            header("Location: ../post.php?post=" . $postId); 
            exit();
        } else {
            echo "Hata: " . $conn->error;
        }

        $stmt->close();
        $conn->close();
    }
} else {
    header("Location: giris.php"); 
    exit();
}
?>