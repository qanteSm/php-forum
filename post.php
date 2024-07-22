<?php
session_start();

require_once "modules/mysqlconn.php";

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    $userId = $_SESSION['id'];

    $sql = "SELECT username FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username = htmlspecialchars($row['username']);
    } else {
        session_destroy();
        header("Location: giris.php?error=error");
        exit();
    }
    $stmt->close();
}

$statusbar = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Forum</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">';

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    $statusbar .= '<li class="nav-item">
                        <a class="nav-link active" href="index.php">Ana Sayfa</a>
                    </li>                            
                    <li class="nav-item">
                        <a class="nav-link" href="modules/cikis.php">Çıkış Yap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">' . $username . '</a>
                    </li>';
} else {
    $statusbar .= '<li class="nav-item">
                        <a class="nav-link active"  href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="giris.php">Giriş</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kaydol.php">Kaydol</a>
                    </li>';
}

$statusbar .= '</ul>
        </div>
    </div>
</nav>';

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Forum</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style type="text/css">
    body {
        background: #eee;
        color: #708090;
    }
    .icon-1x {
        font-size: 24px !important;
    }
    a {
        text-decoration: none;
    }
    .text-primary, a.text-primary:focus, a.text-primary:hover {
        color: #00ADBB!important;
    }
    .text-black, .text-hover-black:hover {
        color: #000 !important;
    }
    .font-weight-bold {
        font-weight: 700 !important;
    }
    .comment-container {
    background-color: #f0f0f0;
    border-radius: 8px;
    padding: 5px;
    margin-bottom: 15px;
    font-weight: 500; 
}
    .comment-content {
        
        padding: 5px 10px; 
        font-size: 14px; 
    }
    .post-title {
    color: black; 
}
</style>
</head>
<body>

<?php echo $statusbar; ?> 

<div class="container mt-5">
    <div class="row">

        <?php

        $postId = isset($_GET['post']) ? (int)$_GET['post'] : null;

        if ($postId) {
            $sql = "SELECT p.*, a.username AS yazar, a.profil_fotografi AS yazar_profil_fotografi
                FROM posts p
                INNER JOIN accounts a ON p.yazar = a.id 
                WHERE p.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $postResult = $stmt->get_result();

            if ($postResult->num_rows > 0) {
                $row = $postResult->fetch_assoc();

                $gecikenSaniye = round(time() - ($row["tarih"] / 1000));
                $dakika = round($gecikenSaniye / 60);
                $saat = round($dakika / 60);
                $gun = round($saat / 24);
                $ay = round($gun / 30);
                $yil = round($ay / 12);

                if ($gecikenSaniye < 60) {
                    $yayinlanmaZamani = "$gecikenSaniye saniye önce";
                } elseif ($dakika < 60) {
                    $yayinlanmaZamani = "$dakika dakika önce";
                } elseif ($saat < 24) {
                    $yayinlanmaZamani = "$saat saat önce";
                } elseif ($gun < 30) {
                    $yayinlanmaZamani = "$gun gün önce";
                } elseif ($ay < 12) {
                    $yayinlanmaZamani = "$ay ay önce";
                } else {
                    $yayinlanmaZamani = "$yil yıl önce";
                }

                echo '<div class="col-lg-12">
                <div class="card row-hover pos-relative py-3 px-3 mb-3 border-warning border-top-0 border-right-0 border-bottom-0 rounded-0">
                    <div class="row align-items-center">
                        <div class="col-md-1">
                            <img src="uploads/profil-fotograflari/' . $row['yazar_profil_fotografi'] . '" alt="Profil Fotoğrafı" class="rounded-circle" style="width: 75px; height: 75px;"> 
                        </div>
                        <div class="col-md-11">
                            <h3><span class="post-title">' . $row["baslik"] . '</span></h3> 
                            <div class="comment-container">  
                                    '.htmlspecialchars($row['icerik']).'
                                
                            </div>
                            <p class="text-sm">
                                <span class="op-6">Posted by</span> <a class="text-black" href="#">' . $row["yazar"] . '</a> <span class="op-6">-</span> <a class="text-black" href="#">' . $yayinlanmaZamani . '</a> |';
        
                        $etiketler = explode(',', $row["etiketler"]);
                        foreach ($etiketler as $etiket) {
                            echo '<a class="text-black mr-2" href="#">  <strong>#' . trim($etiket) . '</strong></a>'; 
                        }
        
                        echo '</p> 
                            <br>
                            <div class="text-sm op-5">';
                        echo '</div>
                        </div>
                    </div>
                </div>
            </div>';
                $sql = "SELECT y.yorum_metni, y.yorum_tarihi, a.username, a.id as kullanici_id 
                        FROM replys y
                        INNER JOIN accounts a ON y.kullanici_id = a.id
                        WHERE y.post_id = ?
                        ORDER BY y.yorum_tarihi ASC"; 

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $yorumlarResult = $stmt->get_result();

                if ($yorumlarResult->num_rows > 0) {
                    echo '<div class="col-lg-12">';
                    while ($yorum = $yorumlarResult->fetch_assoc()) {
                        $yorumcuId = $yorum['kullanici_id']; 
                        $yorumcuProfilFotosu = "fotoyok.jpg"; 

                        $sql = "SELECT profil_fotografi FROM accounts WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $yorumcuId);
                        $stmt->execute();
                        $profilFotoResult = $stmt->get_result();

                        if ($profilFotoResult->num_rows > 0) {
                            $profilFotoRow = $profilFotoResult->fetch_assoc();
                            if (!empty($profilFotoRow['profil_fotografi'])) {
                                $yorumcuProfilFotosu = $profilFotoRow['profil_fotografi'];
                            }
                        }
                        $stmt->close();

                        echo '<div class="card row-hover pos-relative py-3 px-3 mb-3 border-light">
                <div class="row align-items-center">
                    <div class="col-md-1 d-flex align-items-center"> <img src="uploads/profil-fotograflari/' . $yorumcuProfilFotosu . '" alt="Profil Fotoğrafı" class="rounded-circle" style="width: 60px; height: 60px;"> </div>
                    <div class="col-md-11">
                        <div class="d-flex align-items-center"> <p><strong>' . htmlspecialchars($yorum['username']) . '</strong> <small>(' . date('d.m.Y H:i', strtotime($yorum['yorum_tarihi'])) . ')</small></p> </div>
                        <div class="comment-container">  
                            <div class="comment-content">
                                <p>' . htmlspecialchars($yorum['yorum_metni']) . '</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>Bu posta henüz yorum yapılmamış.</p>';
                }

                if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
                    echo '<div class="col-lg-12">
                            <div class="card row-hover pos-relative py-3 px-3 mb-3 border-light">
                                <form action="modules/replypost.php" method="post">
                                    <input type="hidden" name="post_id" value="' . $postId . '">
                                    <div class="form-group">
                                        <textarea class="form-control" name="yorum" rows="3" placeholder="Yorumunuzu buraya yazın..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary mt-2">Yorum Yap</button>
                                </form>
                            </div>
                        </div>';
                } else {
                    echo '<p>Yorum yapmak için <a href="giris.php">giriş yapın</a> veya <a href="kaydol.php">kaydolun</a>.</p>';
                }

            } else {
                echo "Post bulunamadı.";
            }

        } 

        $conn->close();
        ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>