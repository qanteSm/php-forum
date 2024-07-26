<?php
session_start();
require_once "mysqlconn.php"; 
global $conn;

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true && isset($_POST['followedId'])) { 
    $userId = $_SESSION['id']; 
    $followedId = $_POST['followedId'];
    if($userId != $followedId){
        $sql = "SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $followedId);
        $stmt->execute();
        $result = $stmt->get_result();
        $isFollowing = ($result->num_rows > 0);
        $stmt->close();

        if ($isFollowing) {
            $sql = "DELETE FROM followers WHERE follower_id = ? AND followed_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $followedId);
            $stmt->execute();
            $stmt->close();
        } else {
            $sql = "INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $followedId);
            $stmt->execute();
            $stmt->close();

            $sql = "SELECT username FROM accounts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId); 
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $kullaniciAdi = $row['username']; 

            $type = 1;
            $link = "userprofile.php?id=".$userId;
            $content= $kullaniciAdi." adlı kullanıcı sizi takip etemeye başladı!";
            $sql = "INSERT INTO notifications (user_id, maker_id, type, content, link) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisss", $followedId,$userId, $type, $content, $link);
            $basarilia = $stmt->execute(); 
        }
    }
        
}
?>