<?php
session_start();
 
if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    $userId = $_SESSION['id'];
    require_once "modules/ranks.php"; 
    
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

        $userId = $_SESSION['id'];
        $sqlNotifications = "SELECT COUNT(DISTINCT content) AS unread_count FROM notifications WHERE user_id = ? AND read_at IS NULL";
        $stmtNotifications = $conn->prepare($sqlNotifications);
        $stmtNotifications->bind_param("i", $userId);
        $stmtNotifications->execute();
        $resultNotifications = $stmtNotifications->get_result();
        $rowNotifications = $resultNotifications->fetch_assoc();
        $unreadCount = $rowNotifications['unread_count'];

        $notificationClass = $unreadCount > 0 ? 'text-primary' : 'text-secondary'; 

        $statusbar = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
          <a class="navbar-brand" href="#">Forum</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
              <li class="nav-item user-notification-wrapper">
              <a class="nav-link active" href="index.php">Ana Sayfa</a>
              <a class="nav-link" href="modules/cikis.php">Çıkış Yap</a>
              
                <a class="nav-link" href="userprofile.php?id='.$userId.'">' . $username . '</a>
                <a class="nav-link" href="bildirimler.php">
                <button class="inbox-btn">
                  <svg viewBox="0 0 512 512" height="16" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"
                    ></path>
                  </svg>
                  <span class="msg-count">' . ($unreadCount > 0 ? $unreadCount : '0') . '</span>
                </button>
                </a>
                </li>
            </ul>
          </div>
        </div>
      </nav>';
      $stmtNotifications->close();
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
    .user-notification-wrapper {
    display: flex; 
    align-items: center; 
    }
    .card{
      border-radius: 10px;
      transition: border-radius 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .user-notification-wrapper .nav-link {
    margin-right: 10px; 
    display: flex; 
    align-items: center; 
    }

    .user-notification-wrapper .nav-link i {
    font-size: 1rem; 
    }

    .user-notification-wrapper .nav-link .badge {
    font-size: 0.8rem; 
    margin-left: 5px; 
    }
</style>
<style type="text/css">
    @keyframes blinkCursor {
  50% {
    border-right-color: transparent;
  }
}

@keyframes typeAndDelete {
  0%,
  10% {
    width: 0;
  }
  45%,
  55% {
    width: 6.2em;
  } /* adjust width based on content */
  90%,
  100% {
    width: 0;
  }
}
.shadow {
 box-shadow: inset 0 -3em 3em rgba(0,0,0,0.1),
             0 0  0 2px rgb(190, 190, 190),
             0.3em 0.3em 1em rgba(0,0,0,0.3);
}

.terminal-loader {
  border: 0.1em solid #333;
  background-color: #1a1a1a;
  color: #0f0;
  font-family: "Courier New", Courier, monospace;
  font-size: 1em;
  padding: 1.5em 1em;
  width: 12em;
  margin: 100px auto;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  border-radius: 4px;
  position: relative;
  overflow: hidden;
  box-sizing: border-box;
}

.terminal-header {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1.5em;
  background-color: #333;
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
  padding: 0 0.4em;
  box-sizing: border-box;
}

.terminal-controls {
  float: right;
}

.control {
  display: inline-block;
  width: 0.6em;
  height: 0.6em;
  margin-left: 0.4em;
  border-radius: 50%;
  background-color: #777;
}

.control.close {
  background-color: #e33;
}

.control.minimize {
  background-color: #ee0;
}

.control.maximize {
  background-color: #0b0;
}

.terminal-title {
  float: left;
  line-height: 1.5em;
  color: #eee;
}

.text {
  display: inline-block;
  white-space: nowrap;
  overflow: hidden;
  border-right: 0.2em solid green; /* Cursor */
  animation: typeAndDelete 4s steps(11) infinite,
    blinkCursor 0.5s step-end infinite alternate;
  margin-top: 1.5em;
}
.create-post-link {
  display: inline-block; 
  border: 2px solid #0d6efd; 
  background-color: #0d6efd;
  border-radius: 0.9em;
  padding: 0.8em 1.2em;
  transition: all ease-in-out 0.2s;
  font-size: 16px;
  width: 100%; 
  text-decoration: none; 
  color: #fff; 
}

.create-post-link:hover {
  background-color: #fff; 
  color: #000; 
  border: 2px solid black; 
}

.create-post-icon {
  display: inline-block;
  margin-right: 0.5em; 
  color: inherit; 
}

.create-post-text {
  font-weight: 600;
}
.inbox-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: none;
  box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.082);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background-color: #464646;
  cursor: pointer;
  transition: all 0.3s;
}
.inbox-btn svg path {
  fill: white;
}
.inbox-btn svg {
  height: 17px;
  transition: all 0.3s;
}
.msg-count {
  position: absolute;
  top: -5px;
  right: -5px;
  background-color: rgb(255, 255, 255);
  border-radius: 50%;
  font-size: 0.7em;
  color: rgb(0, 0, 0);
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.inbox-btn:hover {
  transform: scale(1.1);
}

</style>
<script>
            window.addEventListener('load', fg_load)
        
            function fg_load() {
                document.getElementById('loading').style.display = 'none'
            }
        </script>
</head>
<body>
<div id="loading">
<div class="terminal-loader">
    <div class="terminal-header">
      <div class="terminal-title">Status</div>
      <div class="terminal-controls">
        <div class="control close"></div>
        <div class="control minimize"></div>
        <div class="control maximize"></div>
      </div>
    </div>
    <div class="text">Loading...</div>
  </div>
</div>
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
        require_once "modules/ranks.php";
        if(isset($_SESSION['entered']) && $_SESSION['entered'] === true && $_SESSION['id']){

            $userRankLevel = getRankLevelById($conn, $_SESSION['id']);
        } else {
            $userRankLevel = 0;
        }
        $sql = "SELECT * 
        FROM posts
        WHERE post_rank <= ? 
        ORDER BY id DESC 
        LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userRankLevel); 
        $stmt->execute();
        $result = $stmt->get_result();

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
                                <div class="card row-hover pos-relative py-3 px-3 mb-3 border-warning border-top-0 border-right-0 border-bottom-0 rounded-100">
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
                <div class="card mb-2 shadow">
                    <div class="card-body">
                        <h5 class="card-title">Hakkında</h5>
                        <p class="card-text">2024 yılında deneme amaçlı açılmış ve geliştirmeye devam edilen bir forum sitesi!</p>
                    </div>
                </div>
            </div>
            <div class="item">
                <div class="card mb-2 shadow">
                    <div class="card-body">
                        <h5 class="card-title">Öne çıkan Postlar    </h5>
                        <div class="card-text">
                        <?php
                            require_once "modules/mysqlconn.php";
                            $sql = "SELECT p.id, p.baslik, COUNT(r.post_id) AS reply_count
                            FROM posts p
                            LEFT JOIN replys r ON p.id = r.post_id
                            WHERE r.yorum_tarihi >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                            AND p.post_rank <= ? 
                            GROUP BY p.id
                            ORDER BY reply_count DESC
                            LIMIT 4;
                            ";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $userRankLevel); 
                    
                            $stmt->execute();

                            $result = $stmt->get_result();
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
                <div class="card mb-2 shadow">
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
            <div class="card mb-2 shadow">
              <div class="card-body">
                <h5 class="card-title">Post Oluştur</h5>
                <a href="postolustur.php" class="create-post-link">
                  <span class="create-post-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"></path><path fill="currentColor" d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"></path></svg>
                  </span>
                  <span class="create-post-text">Oluştur</span>
                </a>
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