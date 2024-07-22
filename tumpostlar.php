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
              </li>                            

              <li class="nav-item">
                <a class="nav-link" href="modules/cikis.php">Çıkış Yap</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="profil.php">' . $username . '</a>
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
<title>bs5 forum list - Bootdey.com</title>
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

<div class="container mt-5"> <div class="row">

    <?php
    require_once "modules/mysqlconn.php";

    $postSayisi = 6;
    $sayfaNumarasi = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
    $baslangic = ($sayfaNumarasi - 1) * $postSayisi;
    $toplamPostSql = "SELECT COUNT(*) FROM posts";
    $toplamPostSonuc = $conn->query($toplamPostSql);
    $toplamPost = $toplamPostSonuc->fetch_assoc()['COUNT(*)'];
    $toplamSayfa = ceil($toplamPost / $postSayisi);
    $sql = "SELECT * FROM posts ORDER BY id DESC LIMIT $baslangic, $postSayisi";
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
                                    <p class="text-sm"><span class="op-6">Posted</span> <a class="text-black" href="#">' . $yayinlanmaZamani . '</a> <span class="op-6">ago by</span> <a class="text-black" href="#">' . $row["yazar"] . '</a></p>
                                    <div class="text-sm op-5">';
                $etiketler = explode(',', $row["etiketler"]);
                foreach ($etiketler as $etiket) {
                    echo '<a class="text-black mr-2" href="#">  #' . trim($etiket) . '</a>';
                }

                echo '</div>
                                </div>
                                <div class="col-md-4 op-7">
                                    <div class="row text-center op-7">
                                        <div class="col px-1"> <i class="ion-connection-bars icon-1x"></i> <span class="d-block text-sm">' . $row["begeniler"] . ' Votes</span> </div>
                                        <div class="col px-1"> <i class="ion-ios-chatboxes-outline icon-1x"></i> <span class="d-block text-sm">' . $row["yorumlar"] . ' Replys</span> </div>
                                        <div class="col px-1"> <i class="ion-ios-eye-outline icon-1x"></i> <span class="d-block text-sm">' . $row["goruntulemeler"] . ' Views</span> </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
            }
        } else {
            echo "Henüz hiç post yok.";
        }
    } else {
        echo "Hata: " . $conn->error;
    }
    
    echo '<nav aria-label="Page navigation example">';
echo '<ul class="pagination">';
if ($sayfaNumarasi > 1) {
    echo '<li class="page-item"><a class="page-link" href="?sayfa=' . ($sayfaNumarasi - 1) . '">Önceki</a></li>';
}

for ($i = 1; $i <= $toplamSayfa; $i++) {
    echo '<li class="page-item' . ($sayfaNumarasi == $i ? ' active' : '') . '"><a class="page-link" href="?sayfa=' . $i . '">' . $i . '</a></li>';
}
if ($sayfaNumarasi < $toplamSayfa) {
    echo '<li class="page-item"><a class="page-link" href="?sayfa=' . ($sayfaNumarasi + 1) . '">Sonraki</a></li>';
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