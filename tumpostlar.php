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
          <a class="navbar-brand" href="index.php">Forum</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
<li class="nav-item">
                <a class="nav-link active" href="index.php">Ana Sayfa</a>
              </li>                            

              <li class="nav-item">
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
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $statusbar = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Forum</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link active"  href="index.php">Ana Sayfa</a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link active"  href="giris.php?yonlendirme_url='. urlencode($current_url) . '">Giriş Yap</a>
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
<title>Tüm Postlar</title>
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
<div class="container mt-5">
    <div class="row">
    <div class="col-lg-12 d-flex justify-content-between mb-3"> 
    <form method="GET" action="" class="d-flex align-items-center"> 
        <div class="input-group">
            <input type="text" class="form-control" name="arama" placeholder="Post ara..." value="<?php echo isset($_GET['arama']) ? htmlspecialchars($_GET['arama']) : ''; ?>">
            <button class="btn btn-outline-secondary" type="submit">Ara</button>
        </div>
        <input type="hidden" name="siralama" value="<?php echo isset($_GET['siralama']) ? $_GET['siralama'] : 'tarih'; ?>">
        <?php 
        if (isset($_GET['fromuser'])) {
            echo '<input type="hidden" name="fromuser" value="' . $_GET['fromuser'] . '">'; 
        }
        ?>
    </form>

    <form method="GET" action="" class="d-flex align-items-center">
        <label for="siralama" class="mr-2">Sırala:</label>
        <select name="siralama" id="siralama" class="form-control form-control-sm mr-2">
            <option value="tarih" <?php if(isset($_GET['siralama']) && $_GET['siralama'] == 'tarih'){echo 'selected';} ?>>Tarih</option> 
            <option value="oy" <?php if(isset($_GET['siralama']) && $_GET['siralama'] == 'oy'){echo 'selected';} ?>>Oy</option>
            <option value="yorum" <?php if(isset($_GET['siralama']) && $_GET['siralama'] == 'yorum'){echo 'selected';} ?>>Yorum Sayısı</option>
            <option value="goruntuleme" <?php if(isset($_GET['siralama']) && $_GET['siralama'] == 'goruntuleme'){echo 'selected';} ?>>Görüntüleme</option>
        </select>

        <?php 
        if (isset($_GET['fromuser'])) {
            echo '<input type="hidden" name="fromuser" value="' . $_GET['fromuser'] . '">'; 
        }
        ?>

        <button type="submit" class="btn btn-sm btn-primary">Uygula</button>
    </form>
</div>

        <?php
        require_once "modules/mysqlconn.php";

        $postSayisi = 6;
        $sayfaNumarasi = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
        $baslangic = ($sayfaNumarasi - 1) * $postSayisi;

        $siralama = isset($_GET['siralama']) ? $_GET['siralama'] : 'tarih'; 
        $arama = isset($_GET['arama']) ? $_GET['arama'] : "";
        $etiket = isset($_GET['etiket']) ? $_GET['etiket'] : "";
        $fromUser = isset($_GET['fromuser']) ? (int)$_GET['fromuser'] : null;

        switch ($siralama) {
            case 'oy':
                $orderBy = "(SELECT COUNT(*) FROM post_likes WHERE liked_post = posts.id) DESC";
                break;
            case 'yorum':
                $orderBy = "(SELECT COUNT(*) FROM replys WHERE replys.post_id = posts.id) DESC";
                break;
            case 'goruntuleme':
                $orderBy = "(SELECT COUNT(DISTINCT visitor_id) FROM post_visits WHERE visited_post_id = posts.id) DESC";
                break;
            default:
                $orderBy = "posts.id DESC";
        }
        require_once "modules/ranks.php";
        $userRank = isset($_SESSION['entered']) && $_SESSION['entered'] === true ? getRankLevelById($conn, $_SESSION['id']) : 0;
        $sqlWhere = "1=1"; 
        if (!empty($etiket)) {
            $etiket = trim($etiket);
            $sqlWhere .= " AND posts.etiketler LIKE '%" . $etiket . "%'";
        }
        if ($fromUser !== null) { 
            $sqlWhere .= " AND posts.yazar = ?"; 
        }
        if (!empty($arama)) {
            $sqlWhere .= " AND (posts.baslik LIKE ? OR posts.icerik LIKE ?)"; 
        }
        $sqlWhere .= " AND posts.post_rank <= ?";
        $sql = "SELECT 
                posts.*,
                (SELECT COUNT(*) FROM post_likes WHERE liked_post = posts.id) AS begeni_sayisi,
                (SELECT COUNT(*) FROM replys WHERE post_id = posts.id) AS yorum_sayisi,
                (SELECT COUNT(DISTINCT visitor_id) FROM post_visits WHERE visited_post_id = posts.id) AS goruntuleme_sayisi
            FROM posts
            LEFT JOIN post_likes ON posts.id = post_likes.liked_post
            LEFT JOIN replys ON posts.id = replys.post_id
            LEFT JOIN post_visits ON posts.id = post_visits.visited_post_id
            WHERE $sqlWhere 
            GROUP BY posts.id
            ORDER BY $orderBy 
            LIMIT $baslangic, $postSayisi";
        
        $stmt = $conn->prepare($sql);

        if ($fromUser !== null && !empty($arama)) { 
            $aramaParam = "%$arama%";
            $stmt->bind_param("issi", $fromUser, $aramaParam, $aramaParam, $userRank);
        } else if ($fromUser !== null) {
            $stmt->bind_param("ii", $fromUser, $userRank);
        } else if (!empty($arama)) {
            $aramaParam = "%$arama%";
            $stmt->bind_param("ssi", $aramaParam, $aramaParam, $userRank);
        } else { 
            $stmt->bind_param("i", $userRank);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {

                    $authorSql = "SELECT username FROM accounts WHERE id = ?";
                    $authorStmt = $conn->prepare($authorSql);
                    $authorStmt->bind_param("i", $row["yazar"]);
                    $authorStmt->execute();
                    $authorResult = $authorStmt->get_result();
                    $authorRow = $authorResult->fetch_assoc();
                    if ($authorResult->num_rows > 0) {
                        $authorUsername = $authorRow['username'];
                    } else {
                        $authorUsername = "Bilinmeyen Kullanıcı";
                    }

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
                                    <div class="col-md-8 mb-3 mb-sm-0">
                                        <h5>
                                            <a href="post.php?post='.$row["id"].'" class="text-primary">' . $row["baslik"] . '</a>
                                        </h5>
                                        <div class="text-sm op-5"><a class="text-black" href="#">' . $row["icerik"] . '</a></div>
                                        <p class="text-sm"><span class="op-6">Posted</span> <a class="text-black" href="#">' . $yayinlanmaZamani . '</a> <span class="op-6">ago by</span> <a class="text-black" href="userprofile.php?id='. $row["yazar"].'">' . $authorUsername . '</a></p>
                                        <div class="text-sm op-5">';
                    $etiketler = explode(',', $row["etiketler"]);
                    foreach ($etiketler as $etiket) {
                        if ($fromUser !== null) { 
                            echo '<a class="text-black mr-2" href="tumpostlar.php?etiket='. trim($etiket) . '&fromuser=' . $fromUser . '">  <strong>#' . trim($etiket) . '</strong></a>';
                        } else {
                            echo '<a class="text-black mr-2" href="tumpostlar.php?etiket='. trim($etiket) . '">  <strong>#' . trim($etiket) . '</strong></a>';
                        }
                    }

                    echo '</div>
                                    </div>
                                    <div class="col-md-4 op-7">
                                        <div class="row text-center op-7">
                                            <div class="col px-1"> <i class="ion-connection-bars icon-1x"></i> <span class="d-block text-sm">' . $row["begeni_sayisi"] . ' Votes</span> </div>
                                            <div class="col px-1"> <i class="ion-ios-chatboxes-outline icon-1x"></i> <span class="d-block text-sm">' . $row["yorum_sayisi"] . ' Replys</span> </div>
                                            <div class="col px-1"> <i class="ion-ios-eye-outline icon-1x"></i> <span class="d-block text-sm">' . $row["goruntuleme_sayisi"] . ' Views</span> </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                }
            } else {
                echo "Aradığınız kriterlere uygun post bulunamadı.";
            }
        } else {
            echo "Hata: " . $conn->error;
        }


        $toplamPostSql = "SELECT COUNT(*) FROM posts WHERE $sqlWhere";
        $toplamPostStmt = $conn->prepare($toplamPostSql);

        if ($fromUser !== null && !empty($arama)) { 
            $aramaParam = "%$arama%";
            $toplamPostStmt->bind_param("issi", $fromUser, $aramaParam, $aramaParam, $userRank); 
        } else if ($fromUser !== null) {
            $toplamPostStmt->bind_param("ii", $fromUser, $userRank); 
        } else if (!empty($arama)) {
            $aramaParam = "%$arama%";
            $toplamPostStmt->bind_param("ssi", $aramaParam, $aramaParam, $userRank);
        } else {
            $toplamPostStmt->bind_param("i", $userRank); 
        }

        $toplamPostStmt->execute();
        $toplamPostSonuc = $toplamPostStmt->get_result();
        $toplamPost = $toplamPostSonuc->fetch_assoc()['COUNT(*)'];


        $toplamSayfa = ceil($toplamPost / $postSayisi);

        echo '<nav aria-label="Page navigation example">';
        echo '<ul class="pagination">';
        if ($sayfaNumarasi > 1) {
            echo '<li class="page-item"><a class="page-link" href="?sayfa=' . ($sayfaNumarasi - 1) . '&siralama=' . $siralama . '&arama=' . $arama . '&etiket=' . $etiket .  (isset($_GET['fromuser']) ? '&fromuser=' . $_GET['fromuser'] : '') . '">Önceki</a></li>';
        }

        for ($i = 1; $i <= $toplamSayfa; $i++) {
            echo '<li class="page-item' . ($sayfaNumarasi == $i ? ' active' : '') . '"><a class="page-link" href="?sayfa=' . $i . '&siralama=' . $siralama . '&arama=' . $arama . '&etiket=' . $etiket . (isset($_GET['fromuser']) ? '&fromuser=' . $_GET['fromuser'] : '')  . '">' . $i . '</a></li>';
        }

        if ($sayfaNumarasi < $toplamSayfa) {
            echo '<li class="page-item"><a class="page-link" href="?sayfa=' . ($sayfaNumarasi + 1) . '&siralama=' . $siralama . '&arama=' . $arama . '&etiket=' . $etiket . (isset($_GET['fromuser']) ? '&fromuser=' . $_GET['fromuser'] : '') . '">Sonraki</a></li>';
        }

        echo '</ul>';
        echo '</nav>';
        $conn->close();
        ?>

    </div> 
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>