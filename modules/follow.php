<?php
session_start();
require_once "mysqlconn.php"; 
global $conn;

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true && isset($_POST['followedId'])) { 
    $userId = $_SESSION['id']; 
    $followedId = $conn->real_escape_string($_POST['followedId']); 

    if($userId != $followedId){
        $isFollowing = isFollowing($conn, $userId, $followedId);

        if ($isFollowing) {
            $success = unfollowUser($conn, $userId, $followedId);
            $response = ['success' => $success, 'action' => 'unfollow'];
        } else {
            $success = followUser($conn, $userId, $followedId);
            if($success){
                $kullaniciAdi = getUser($conn,$userId);
                $type = 1;
                $link = "userprofile.php?id=".$userId;
                $content= $kullaniciAdi." adlı kullanıcı sizi takip etemeye başladı!";
                sendNotification($conn, $followedId, $userId, $type, $content, $link);
            }
            $response = ['success' => $success, 'action' => 'follow']; 
        }
        echo json_encode($response);
    }
}

function isFollowing($conn, $userId, $followedId){
    $sql = "SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $followedId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isFollowing = ($result->num_rows > 0);
    $stmt->close();
    return $isFollowing;
}
function unfollowUser($conn, $userId, $followedId){
    $sql = "DELETE FROM followers WHERE follower_id = ? AND followed_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $followedId);
    if(!$stmt->execute()){
        return false;
    }
    $stmt->close();
    return true;
}

function followUser($conn, $userId, $followedId){
    $sql = "INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $followedId);
    if(!$stmt->execute()){
        return false;
    }
    $stmt->close();
    return true;
}

function getUser($conn, $userId){
    $sql = "SELECT username FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['username']; 
}

function sendNotification($conn, $followedId, $userId, $type, $content, $link){
    $sql = "INSERT INTO notifications (user_id, maker_id, type, content, link) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $followedId,$userId, $type, $content, $link);
    if(!$stmt->execute()){
        return false;
    }
    return true;
}
?>