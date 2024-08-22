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

    .text-baslik, a.text-baslik:focus, a.text-baslik:hover {
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
  background-color: #0a58ca;
  cursor: pointer;
  transition: all 0.3s;
}
.inbox-btn svg path {
  fill: white;
  transition: fill .2s ease;
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
  background-color: #ffff;
  border: 1px solid #000;
  border-radius: 9999px; 
  

} 

.inbox-btn:hover svg path {
  fill: #000 !important;
}
.tum-postlar-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #0a58ca;
  border-radius: 9999px;
  background-color: #0a58ca;
  padding: 0.625rem 1.5rem;
  text-align: center;
  color: rgba(255, 255, 255, 1);
  outline: 0;
  transition: all  .2s ease;
  text-decoration: none;
}

.tum-postlar-button:hover {
  background-color: transparent;
  color: rgba(0, 0, 0, 1);
  border: 2px solid rgba(0, 0, 0, 1);
}
.tum-postlar-button:hover .tum-postlar-icon {
  fill: #000; 
}
 
.tum-postlar-icon {
  height: 1.5rem;
  width: 1.5rem;
  margin-right: 0.5rem;
  transition: fill .2s ease;
}

.tum-postlar-texts {
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1;
}

.tum-postlar-text {
  font-weight: 600;
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
        <a class="tum-postlar-button" href="tumpostlar.php">
        <svg height="24" width="24" style="margin-right: 10px;" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="currentColor" class="tum-postlar-icon">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier"> 
              <g> 
                <path class="st0" d="M496.872,423.839l-85.357-85.358c-4.76,7.322-9.901,14.378-15.392,21.142l-31.484-31.485 c1.357-1.771,2.7-3.556,4.014-5.371c0.885-1.225,1.756-2.45,2.596-3.689c0.148-0.192,0.28-0.398,0.414-0.59 c0.855-1.254,1.711-2.523,2.538-3.792c1.8-2.744,3.526-5.518,5.179-8.351c17.691-30.174,27.857-65.291,27.857-102.725 s-10.166-72.55-27.857-102.724c-17.692-30.145-42.893-55.346-73.037-73.038C276.168,10.166,241.052,0,203.618,0 c-37.433,0-72.55,10.166-102.724,27.858c-1.239,0.723-2.464,1.461-3.689,2.228c-1.564,0.959-3.128,1.948-4.663,2.951 c-2.729,1.785-5.429,3.63-8.07,5.548c-0.886,0.634-1.756,1.284-2.627,1.933c-0.914,0.694-1.829,1.387-2.744,2.081 c-0.841,0.664-1.697,1.328-2.538,2.006c-1.653,1.328-3.29,2.671-4.899,4.058C63.402,55.7,55.7,63.402,48.662,71.665 c-1.387,1.608-2.73,3.232-4.058,4.899c-0.678,0.841-1.343,1.697-2.006,2.538c-0.694,0.915-1.387,1.83-2.08,2.744 c-0.649,0.87-1.298,1.741-1.933,2.626c-1.918,2.641-3.762,5.341-5.548,8.071c-1.004,1.535-1.992,3.099-2.951,4.663 c-0.767,1.224-1.505,2.449-2.228,3.689C10.166,131.069,0,166.186,0,203.62s10.166,72.55,27.857,102.725 c17.692,30.144,42.893,55.346,73.037,73.037c30.174,17.692,65.291,27.858,102.724,27.858c37.434,0,72.55-10.166,102.724-27.858 c1.888-1.106,3.748-2.243,5.592-3.408c0.929-0.575,1.844-1.166,2.759-1.77c1.269-0.826,2.538-1.682,3.792-2.538 c0.192-0.133,0.398-0.266,0.59-0.413c1.239-0.841,2.464-1.712,3.689-2.597c1.815-1.313,3.6-2.656,5.371-4.013l31.483,31.483 c-6.764,5.49-13.82,10.632-21.14,15.393l85.358,85.358C433.913,506.954,447.134,512,460.354,512s26.441-5.046,36.518-15.124 C517.042,476.706,517.042,444.009,496.872,423.839z M284.682,323.283c-0.413,0.295-0.826,0.575-1.254,0.841 c-0.472,0.34-0.959,0.649-1.446,0.959c-0.442,0.295-0.886,0.575-1.328,0.856c-0.576,0.369-1.15,0.723-1.726,1.062 c-0.546,0.34-1.077,0.664-1.623,0.989c-1.166,0.694-2.332,1.357-3.512,2.021c-0.089,0.059-0.177,0.104-0.28,0.162 c-0.96,0.531-1.933,1.048-2.907,1.549c-0.384,0.222-0.768,0.428-1.166,0.62c-0.767,0.398-1.549,0.782-2.33,1.166 c-1.491,0.738-2.996,1.446-4.516,2.124c-18.016,8.086-37.979,12.586-58.975,12.586c-20.996,0-40.959-4.5-58.975-12.586 c-32.403-14.519-58.518-40.635-73.037-73.037C63.52,244.58,59.02,224.616,59.02,203.62s4.5-40.96,12.586-58.976 c2.272-5.061,4.824-9.974,7.643-14.711c0.325-0.546,0.649-1.077,0.988-1.623c0.915-1.52,1.874-3.025,2.878-4.5 c0.546-0.841,1.106-1.667,1.696-2.494c0.694-1.033,1.416-2.051,2.17-3.054c1.135-1.549,2.301-3.084,3.496-4.589 c6.832-8.572,14.622-16.363,23.195-23.195c1.505-1.195,3.04-2.361,4.589-3.497c1.004-0.753,2.022-1.476,3.054-2.169 c0.827-0.59,1.653-1.151,2.494-1.697c1.476-1.003,2.981-1.962,4.5-2.877c0.546-0.34,1.077-0.664,1.623-0.989 c4.736-2.818,9.65-5.371,14.711-7.643c18.016-8.086,37.979-12.586,58.975-12.586c20.996,0,40.96,4.5,58.975,12.586 c32.402,14.519,58.518,40.635,73.037,73.037c8.086,18.016,12.586,37.98,12.586,58.976s-4.5,40.96-12.586,58.976 c-0.679,1.52-1.386,3.025-2.124,4.515c-0.384,0.782-0.768,1.564-1.166,2.332c-0.192,0.398-0.399,0.782-0.62,1.166 c-0.502,0.974-1.018,1.948-1.549,2.907c-0.059,0.103-0.103,0.192-0.162,0.28c-0.65,1.18-1.328,2.346-2.022,3.512 c-0.325,0.546-0.649,1.077-0.988,1.623c-0.339,0.576-0.694,1.151-1.063,1.726c-0.28,0.443-0.56,0.886-0.856,1.328 c-0.31,0.487-0.62,0.974-0.959,1.446c-0.265,0.428-0.546,0.841-0.841,1.254c-0.28,0.413-0.561,0.826-0.856,1.239 c-0.148,0.251-0.325,0.502-0.516,0.738c-0.324,0.487-0.679,0.989-1.033,1.476c-2.685,3.733-5.548,7.319-8.587,10.756 c-0.545,0.635-1.106,1.254-1.667,1.874c-0.723,0.797-1.446,1.594-2.184,2.361c-0.856,0.9-1.741,1.8-2.627,2.686 c-0.884,0.885-1.785,1.77-2.685,2.626c-0.767,0.738-1.564,1.46-2.361,2.184c-0.62,0.561-1.239,1.121-1.874,1.667 c-3.437,3.04-7.023,5.902-10.756,8.588c-0.487,0.354-0.989,0.708-1.476,1.033c-0.236,0.192-0.487,0.369-0.738,0.516 C285.508,322.722,285.094,323.003,284.682,323.283z"></path> 
              </g> 
            </g>
          </svg>
          <span class="tum-postlar-texts">
            <span class="tum-postlar-text">Tüm Postları Gör</span>
          </span>
        </a>
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
                                                <a href="post.php?post='.$row["id"].'" class="text-baslik">' . $row["baslik"] . '</a>
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