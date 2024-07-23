<?php
session_start();

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    $userId = $_SESSION['id'];

    require_once "modules/mysqlconn.php";
    $sql = "SELECT username FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username = htmlspecialchars($row['username']);
        $welcomeMessage = "Welcome, " . $username . "!";
        $statusbar = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
          <a class="navbar-brand" href="#">Forum</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
<li class="nav-item">
                <a class="nav-link active" href="index.php">Ana Sayfa</a>
              </li>              <li class="nav-item">
                <a class="nav-link" href="modules/cikis.php">Çıkış Yap</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="userprofile.php?id='.$userId.'">' . $username . '</a>
              </li>
            </ul>
          </div>
        </div>
      </nav>';
    } else {
        session_destroy();
        header("Location: giris.php?error=error");
        exit();
    }

    $stmt->close();
} else {
    $statusbar = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Forum</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link active"  href="index.php">Ana Sayfa</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="giris.php">Giriş</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="kaydol.php">Kaydol</a>
        </li>
      </ul>
    </div>
  </div>
</nav>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Ferwle's Forum</title>
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
</style>
</head>
<body>
<?php echo $statusbar; ?>

<link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet">

<!-- Üst Menü Başlangıcı -->
<div class="container mt-3">
  <div class="row">
    <div class="col-lg-12 mb-3"> 
      <div class="d-flex align-items-end justify-content-between"> 
        <a href="tumpostlar.php" class="btn btn-primary">Tüm Postlar</a>
        <p class="text-muted mb-0">En son gönderilen 5 post listelendi</p>
      </div>
    </div>
  </div>
</div>

<!-- Üst Menü Sonu -->

<div class="container">
  <div class="row">
    <div class="col-lg-9">
      <div class="row">
  <?php
        require_once "modules/mysqlconn.php";
        $sql = "SELECT * FROM posts ORDER BY id DESC LIMIT 5";
        $result = $conn->query($sql);

        if ($result) {
            if ($result->num_rows > 0) {
                $ilkPost = true; 
                while ($row = $result->fetch_assoc()) {
                    $postId = $row["id"]; 

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
                    
                    $yazarId = $row["yazar"]; 
                    $sqlKullaniciAdi = "SELECT username FROM accounts WHERE id = ?";
                    $stmtKullaniciAdi = $conn->prepare($sqlKullaniciAdi);
                    $stmtKullaniciAdi->bind_param("i", $yazarId);
                    $stmtKullaniciAdi->execute();
                    $resultKullaniciAdi = $stmtKullaniciAdi->get_result();

                    if ($resultKullaniciAdi->num_rows > 0) {
                        $rowKullaniciAdi = $resultKullaniciAdi->fetch_assoc();
                        $username = $rowKullaniciAdi['username']; 
                        $girenadaminid = $rowKullaniciAdi['username']; 
                    } else {
                        $username = "Bilinmeyen Kullanıcı"; 
                    }

                    $stmtKullaniciAdi->close();

                    $sqlGoruntuleme = "SELECT DISTINCT visitor_id FROM post_visits WHERE visited_post_id = ?";
                    $stmtGoruntuleme = $conn->prepare($sqlGoruntuleme);
                    $stmtGoruntuleme->bind_param("i", $postId);
                    $stmtGoruntuleme->execute();
                    $resultGoruntuleme = $stmtGoruntuleme->get_result();
                    $goruntulemeSayisi = $resultGoruntuleme->num_rows;
                    $stmtGoruntuleme->close();

                    $sqlYorumSayisi = "SELECT COUNT(*) AS yorum_sayisi FROM replys WHERE post_id = ?";
                    $stmtYorumSayisi = $conn->prepare($sqlYorumSayisi);
                    $stmtYorumSayisi->bind_param("i", $postId);
                    $stmtYorumSayisi->execute();
                    $resultYorumSayisi = $stmtYorumSayisi->get_result();
                    $rowYorumSayisi = $resultYorumSayisi->fetch_assoc();
                    $yorumSayisi = $rowYorumSayisi['yorum_sayisi'];
                    $stmtYorumSayisi->close();

                    $sqlBegeniSayisi = "SELECT COUNT(*) AS yorum_sayisi FROM post_likes WHERE liked_post = ?";
                    $stmtBegeniSayisi = $conn->prepare($sqlBegeniSayisi);
                    $stmtBegeniSayisi->bind_param("i", $postId);
                    $stmtBegeniSayisi->execute();
                    $resultBegeniSayisi = $stmtBegeniSayisi->get_result();
                    $rowBegeniSayisi = $resultBegeniSayisi->fetch_assoc();
                    $BegeniSayisi = $rowBegeniSayisi['yorum_sayisi'];
                    $stmtBegeniSayisi->close();


                    if ($ilkPost) {
                        echo '<div class="col-lg-12"> 
                                <div class="card row-hover pos-relative py-3 px-3 mb-3 border-warning border-top-0 border-right-0 border-bottom-0 rounded-0">
                                    <div class="row align-items-center">
                                        <div class="col-md-8 mb-3 mb-sm-0">
                                            <h5>
                                                <a href="post.php?post='.$row["id"].'" class="text-primary">' . $row["baslik"] . '</a>
                                            </h5>
                                            <div class="text-sm op-5"><a class="text-black" href="#">' . $row["icerik"] . '</a></div>
                                            <p class="text-sm"><span class="op-6">Posted</span> <a class="text-black" href="#">' . $yayinlanmaZamani . '</a> <span class="op-6">ago by</span> <a class="text-black" href="userprofile.php?id='.$yazarId.'">' . $username . '</a></p>
                                            <div class="text-sm op-5">';
                        $etiketler = explode(',', $row["etiketler"]);
                        foreach ($etiketler as $etiket) {
                            echo '<a class="text-black mr-2" href="tumpostlar.php?etiket='. trim($etiket) . '">  <strong>#' . trim($etiket) . '</strong></a>'; 
                        }

                        echo '</div>
                                        </div>
                                        <div class="col-md-4 op-7">
                                            <div class="row text-center op-7">
                                                <div class="col px-1"> <i class="ion-connection-bars icon-1x"></i> <span class="d-block text-sm">' . $BegeniSayisi . ' Votes</span> </div>
                                                <div class="col px-1"> <i class="ion-ios-chatboxes-outline icon-1x"></i> <span class="d-block text-sm">' . $yorumSayisi . ' Replys</span> </div>
                                                <div class="col px-1"> <i class="ion-ios-eye-outline icon-1x"></i> <span class="d-block text-sm">' . $goruntulemeSayisi . ' Views</span> </div> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        $ilkPost = false; 
                    } else {
                        echo '<div class="col-lg-6">
                                <div class="card row-hover pos-relative py-3 px-3 mb-3 border-warning border-top-0 border-right-0 border-bottom-0 rounded-0">
                                    <div class="row align-items-center">
                                        <div class="col-md-8 mb-3 mb-sm-0">
                                            <h5>
                                                <a href="post.php?post='.$row["id"].'" class="text-primary">' . $row["baslik"] . '</a>
                                            </h5>
                                            <div class="text-sm op-5"><a class="text-black" href="#">' . $row["icerik"] . '</a></div>
                                            <p class="text-sm"><span class="op-6">Posted</span> <a class="text-black" href="#">' . $yayinlanmaZamani . '</a> <span class="op-6">ago by</span> <a class="text-black" href="userprofile.php?id='.$yazarId.'">' . $username . '</a></p>
                                            <div class="text-sm op-5">';
                        $etiketler = explode(',', $row["etiketler"]);
                        foreach ($etiketler as $etiket) {
                            echo '<a class="text-black mr-2" href="tumpostlar.php?etiket='. trim($etiket) . '">  <strong>#' . trim($etiket) . '</strong></a>';
                        }

                        echo '</div>
                                        </div>
                                        <div class="col-md-4 op-7">
                                            <div class="row text-center op-7">
                                                <div class="col px-1"> <i class="ion-connection-bars icon-1x"></i> <span class="d-block text-sm">' . $BegeniSayisi . ' Votes</span> </div>
                                                <div class="col px-1"> <i class="ion-ios-chatboxes-outline icon-1x"></i> <span class="d-block text-sm">' . $yorumSayisi . ' Replys</span> </div>
                                                <div class="col px-1"> <i class="ion-ios-eye-outline icon-1x"></i> <span class="d-block text-sm">' . $goruntulemeSayisi . ' Views</span> </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    }
                }
            } else {
                echo "Henüz hiç post yok.";
            }
        } else {
            echo "Hata: " . $conn->error;
        }

        
        ?>
      </div> 
    </div>
    <div class="col-lg-3 mb-4 mb-lg-0 px-lg-0 mt-lg-0">
        <div data-children=".item" class="pl-lg-4">
            <div class="item">
                <div class="card mb-2">
                    <div class="card-body">
                        <h5 class="card-title">Hakkında</h5>
                        <p class="card-text">2024 yılında deneme amaçlı açılmış ve geliştirmeye devam edilen bir forum sitesi!</p>
                    </div>
                </div>
            </div>
            <div class="item">
                <div class="card mb-2">
                    <div class="card-body">
                        <h5 class="card-title">Öne çıkan Postlar    </h5>
                        <div class="card-text">
                        <?php
                            require_once "modules/mysqlconn.php";
                            $sql = "SELECT p.id, p.baslik, COUNT(r.post_id) AS reply_count
                            FROM posts p
                            LEFT JOIN replys r ON p.id = r.post_id
                            WHERE r.yorum_tarihi >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                            GROUP BY p.id
                            ORDER BY reply_count DESC
                            LIMIT 4;
                            ";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                echo "<ul>";
                                while ($row = $result->fetch_assoc()) {
                                    $postId = $row["id"];
                                    $baslik = $row["baslik"];
                                    $reply_count = $row["reply_count"];
                                    echo '<li><a href="post.php?post=' . $postId . '" class="d-block mb-2">' . $baslik . '</a></li>';
                                }
                                echo "</ul>";
                            } else {
                                echo "Son 1 saat içinde yorum alan post bulunamadı.";
                            }
                        ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="item">
                <div class="card mb-2">
                    <div class="card-body">
                        <h5 class="card-title">İstatistikler</h5>
                        <p class="card-text"><b>Kullanıcı Sayısı:</b> 
                        <?php 
                            $sql = "SELECT COUNT(*) FROM accounts";
                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            if ($result->num_rows > 0) {
                                echo $row['COUNT(*)'];
                                }
                        ?>
                        </p>
                        <p class="card-text"><b>Postlar:</b> 
                        <?php 
                            $sql = "SELECT COUNT(*) FROM posts";
                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            if ($result->num_rows > 0) {
                                echo $row['COUNT(*)'];
                                }
                        ?>
                        </p>
                        <p class="card-text"><b>Yorumlar:</b> 
                        <?php 
                            $sql = "SELECT COUNT(*) FROM replys";
                            $result = $conn->query($sql);
                            $row = $result->fetch_assoc();
                            if ($result->num_rows > 0) {
                            echo $row['COUNT(*)'];
                            }
                        ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="item">
                <div class="card mb-2">
                  <div class="card-body">
                    <h5 class="card-title">Create Post</h5>
                  <a href="postolustur.php" class="btn btn-primary w-100">Yeni Post Oluştur</a>
                  </div>
                </div>
          </div>
        </div>
    </div>
  </div>
</div>
<?php 
    $conn->close();
?>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>