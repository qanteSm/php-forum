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
            $yeniYorumId = $conn->insert_id;

            $sqlYorumSayisi = "SELECT COUNT(*) FROM replys WHERE post_id = ?";
            $stmtYorumSayisi = $conn->prepare($sqlYorumSayisi);
            $stmtYorumSayisi->bind_param("i", $postId);
            $stmtYorumSayisi->execute();
            $resultYorumSayisi = $stmtYorumSayisi->get_result();
            $toplamYorumSayisi = $resultYorumSayisi->fetch_row()[0];

            $stmtYorumSayisi->close();

            $yeniYorumSirasi = $toplamYorumSayisi; 


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
            $stmt->bind_param("i", $_SESSION['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $kullaniciAdi = $row['username'];

            if ($_SESSION['id'] != $postYazarId) {
                $type = "2";
                $content = "$kullaniciAdi adlı kullanıcı ";

                if (strlen($postBaslik) > 20) { 
                    $postBaslik = substr($postBaslik, 0, 20) . "...";
                }
                $content .= "'$postBaslik' başlıklı postunuzu cevapladı.";

                $link = "post.php?post=" . $postId."&message=".$yeniYorumSirasi."";
                $sql = "INSERT INTO notifications (user_id, maker_id, type, content, link) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisss", $postYazarId, $_SESSION['id'], $type, $content, $link); 
                $basarilia = $stmt->execute();
            }

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