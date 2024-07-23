<?php

function getRankNameById($conn, $user_id) {
  $sql = "SELECT r.rank_name 
          FROM accounts u
          JOIN Ranks r ON u.rank = r.rank_level
          WHERE u.id = ?";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();

  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    return $row["rank_name"];
  } else {
    return "Kullanıcı bulunamadı.";
  }

  $stmt->close();
}

function getRankLevelById($conn, $user_id) {
    $sql = "SELECT r.rank_level 
            FROM accounts u
            JOIN Ranks r ON u.rank_id = r.id
            WHERE u.id = ?";
  
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
  
    $result = $stmt->get_result();
  
    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      return $row["rank_level"];
    } else {
      return "Kullanıcı bulunamadı.";
    }
  
    $stmt->close();
  }
?>