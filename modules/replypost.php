<?php
session_start();
date_default_timezone_set('Europe/Istanbul');
if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    require_once "mysqlconn.php";
    require_once "ranks.php";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postId = $_POST['post_id'];
        $yorum = htmlspecialchars($_POST['yorum']);

        if (empty($yorum)) {
            $errormessage = "Yorum metni boş olamaz.";
            header("Location: ../post.php?post=" . $postId."&error=".$errormessage);
        }

        if ($conn->connect_error) {
            die("Bağlantı hatası: " . $conn->connect_error);
        }
        $userLevel = getRankLevelById($conn, $_SESSION['id']);
        
        if (strpos($yorum, "!forumbot") === 0) {
            if ($userLevel >= 2){
                $komutArgs = explode(" ", trim(substr($yorum, strlen("!forumbot"))));
                 if (!isset($_POST['onay'])) {
                    echo "<form method='post' action=''>";
                    echo "<input type='hidden' name='post_id' value='$postId'>";
                    echo "<input type='hidden' name='yorum' value='$yorum'>";
                    echo "<p>Bu işlemi gerçekleştirmek istediğinizden emin misiniz?</p>";
                    echo "<button type='submit' name='onay' value='evet'>Evet</button>";
                    echo "<button type='submit' name='onay' value='hayir'>Hayır</button>";
                    echo "</form>";
                    exit();
                }
                if ($_POST['onay'] === 'evet') {
                    if ($komutArgs[0] == "timeout") {
                        if (count($komutArgs) < 4) {
                            $errormessage = "Hata: Yanlış komut kullanımı. Örnek: !forumbot timeout kullaniciAdi sebep sure(dakika)";
                            header("Location: ../post.php?post=" . $postId."&error=".$errormessage);
                            exit();
                        }

                        $timeoutKullaniciAdi = $komutArgs[1];
                        $timeoutSebep = $komutArgs[2];
                        $timeoutSure = intval($komutArgs[3]);

                        $sql = "SELECT id FROM accounts WHERE username = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $timeoutKullaniciAdi);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $timeoutKullaniciId = $result->fetch_assoc()['id'];

                            $bitisZamani = date('Y-m-d H:i:s', strtotime("+$timeoutSure minutes"));

                            $sql = "INSERT INTO timeouts (user_id, timeout_atan, sebep, sure, bitis_zamani) 
                                    VALUES (?, ?, ?, ?, ?)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("iisss", $timeoutKullaniciId, $_SESSION['id'], $timeoutSebep, $timeoutSure, $bitisZamani);

                            if ($stmt->execute()) {
                                echo "$timeoutKullaniciAdi adlı kullanıcı $timeoutSure dakika süreyle susturuldu. Sebep: $timeoutSebep";
                            } else {
                                $errormessage = "Hata: Timeout veritabanına kaydedilemedi. " . $conn->error;
                                header("Location: ../post.php?post=" . $postId."&error=".$errormessage);
                            }
                        } else {
                            $errormessage = "kullanıcı bulunamadı";
                            header("Location: ../post.php?post=" . $postId."&error=".$errormessage);
                        }
                    }elseif($komutArgs[0] == "deletemessage"){
                        $silinecekYorumSirasi = intval($komutArgs[1]); 

                        $sql = "SELECT id FROM replys WHERE post_id = ? ORDER BY yorum_tarihi ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $postId);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $yorumIdleri = [];
                        while ($row = $result->fetch_assoc()) {
                            $yorumIdleri[] = $row['id'];
                        }

                        if (isset($yorumIdleri[$silinecekYorumSirasi - 1])) { 
                            $silinecekYorumId = $yorumIdleri[$silinecekYorumSirasi - 1];

                            $sql = "DELETE FROM replys WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $silinecekYorumId);

                            if ($stmt->execute()) {
                                
                                header("Location: ../post.php?post=" . $postId);
                            } else {
                                $errormessage = "Hata: Silinemedi. " . $conn->error;
                                header("Location: ../post.php?post=" . $postId."&error=".$errormessage);
                            }
                        } else {
                            $errormessage = "Hata: Geçersiz yorum sırası.";
                                header("Location: ../post.php?post=" . $postId."&error=".$errormessage);
                        }

                    }elseif($komutArgs[0] == "deletemessagesbetween"){
                        $baslangicSirasi = intval($komutArgs[1]);
                        $bitisSirasi = intval($komutArgs[2]); 

                        $sql = "SELECT id FROM replys WHERE post_id = ? ORDER BY yorum_tarihi ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $postId);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $yorumIdleri = [];
                        while ($row = $result->fetch_assoc()) {
                            $yorumIdleri[] = $row['id'];
                        }

                        if ($baslangicSirasi < 1 || $bitisSirasi > count($yorumIdleri) || $baslangicSirasi > $bitisSirasi) {
                            echo "Hata: Geçersiz sıra numaraları.";
                            exit();
                        }

                        for ($i = $baslangicSirasi - 1; $i < $bitisSirasi; $i++) {
                            $silinecekYorumId = $yorumIdleri[$i];

                            $sql = "DELETE FROM replys WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $silinecekYorumId);
                            $stmt->execute();
                        }

                        header("Location: ../post.php?post=" . $postId);
                    }elseif($komutArgs[0] == "deleteallmessages"){
                        $sql = "DELETE FROM replys WHERE post_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $postId);
                        $stmt->execute();

                        header("Location: ../post.php?post=" . $postId);
                    }elseif($komutArgs[0] == "deletemessages"){
                        $silinecekYorumSayisi = intval($komutArgs[1]); 

                        $sql = "SELECT id FROM replys WHERE post_id = ? ORDER BY yorum_tarihi ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $postId);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $yorumIdleri = [];
                        while ($row = $result->fetch_assoc()) {
                            $yorumIdleri[] = $row['id'];
                        }

                        $silinecekYorumSayisi = min($silinecekYorumSayisi, count($yorumIdleri));

                        for ($i = 0; $i < $silinecekYorumSayisi; $i++) {
                            $silinecekYorumId = $yorumIdleri[$i];

                            $sql = "DELETE FROM replys WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $silinecekYorumId);
                            $stmt->execute();
                        }
                        
                        header("Location: ../post.php?post=" . $postId);
                    }else {
                        header("Location: ../post.php?post=" . $postId);
                    }
                }else {
                    header("Location: ../post.php?post=" . $postId);
                }

            } else {
                header("Location: ../post.php?post=" . $postId);
            }
            
        } else {
            
            $sql = "SELECT bitis_zamani,sebep FROM timeouts 
                WHERE user_id = ? AND bitis_zamani > NOW() 
                ORDER BY bitis_zamani DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $kalanSure = strtotime($row['bitis_zamani']) - time();
            $saat = floor($kalanSure / 3600);
            $dakika = floor(($kalanSure % 3600) / 60);
            $saniye = $kalanSure % 60;

            $errormessage = "Yorum yapamazsınız. Kalan timeout süreniz: ".$saat." saat, ".$dakika." dakika, ".$saniye." saniye. Tİmeout sebebi: ".$row['sebep'];
            header("Location: ../post.php?post=" . $postId."&error=".$errormessage);
        }else {

            function kullaniciAdiniBul($metin, $conn, $baslikreal,$postId,$kacinci) {
                $desen = '/\(user@(\d+)\)/'; 
                preg_match_all($desen, $metin, $eslesmeler);
            
                if (!empty($eslesmeler)) {
                    foreach ($eslesmeler[1] as $kullaniciId) {
                        $sql = "SELECT username,id FROM accounts WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $kullaniciId);
                        $stmt->execute();
                        $result = $stmt->get_result();
            
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $username = $row['username'];
                            $gonderilecekuserid = $row['id'];
        
                            $sql = "SELECT username FROM accounts WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['id']); 
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            $kullaniciAdi = $row['username']; 
        
                            $type = "4"; 
                            $content = "$kullaniciAdi adlı kullanıcı "; 
                            $baslikk = $baslikreal;
                            if (strlen($baslikk) > 20) {
                            $baslikk = substr($baslikk, 0, 15) . "...";
                            }
                            $content .= "'$baslikk' başlıklı postun yorumunda sizi etiketledi.";   
                            $link = "post.php?post=".$postId."&message=".$kacinci; 
                            if ($gonderilecekuserid != $_SESSION['id']){
        
                                $sql = "INSERT INTO notifications (user_id, maker_id, type, content, link) VALUES (?, ?, ?, ?, ?)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("iisss", $gonderilecekuserid,$_SESSION['id'], $type, $content, $link);
                                $basarilia = $stmt->execute();
                            }
                        }
                        $stmt->close();
                    }
                }
            }

            $userank = getRankLevelById($conn,$_SESSION['id']);
            $postrank = getRankLevelByPostId($conn,$postId);
            if($userank >= $postrank){
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
                    kullaniciAdiniBul($yorum,$conn,$postBaslik,$postId,$yeniYorumSirasi);
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
            }else {
                echo "oç";
            }
            
        }
            
            
        }
    }
} else {
    header("Location: giris.php"); 
    exit();
}
?>