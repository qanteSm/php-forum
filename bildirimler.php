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

        $sqlMarkRead = "UPDATE notifications SET read_at = NOW() WHERE user_id = ?";
        $stmtMarkRead = $conn->prepare($sqlMarkRead);
        $stmtMarkRead->bind_param("i", $userId);
        $stmtMarkRead->execute();

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
                <a class="nav-link" href="userprofile.php?id='.$userId.'">' . $username . '</a>
              </li>
              <li class="nav-item">
                <a class="nav-link text-secondary" href="bildirimler.php">
                  <i class="ion-ios-bell-outline icon-1x text-secondary"></i>
                </a>
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
    header("Location: giris.php?error=error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Ferwle's Forum</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet">
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

<div class="container mt-5">
  <div class="row">
    <div class="col-lg-12">
      <h2>Bildirimler</h2>
      <div class="mt-3">
        <?php
        require_once "modules/mysqlconn.php";
        $sql = "SELECT DISTINCT content, MAX(created_at) as latest_created_at, link,maker_id FROM notifications WHERE user_id = ? GROUP BY content, link ORDER BY latest_created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                require_once "modules/mysqlconn.php";

                    $sqlKullaniciAdi = "SELECT username,id FROM accounts WHERE id = ?";
                    $stmtKullaniciAdi = $conn->prepare($sqlKullaniciAdi);
                    $stmtKullaniciAdi->bind_param("i", $row['maker_id']);
                    $stmtKullaniciAdi->execute();
                    $resultKullaniciAdi = $stmtKullaniciAdi->get_result();

                    if ($resultKullaniciAdi->num_rows > 0) {
                        $rowKullaniciAdi = $resultKullaniciAdi->fetch_assoc();
                        $username = $rowKullaniciAdi['username']; 
                        $girenadaminid = $rowKullaniciAdi['id']; 
                    } else {
                        $username = "Bilinmeyen Kullanıcı"; 
                        $girenadaminid = 0; 
                    }
                echo '<div class="card mb-2">
                        <div class="card-body">
                          <p><a href="' . $row['link'] . '" class="text-decoration-none" style="font-weight: bold;">' . $row['content'] . '</a></p> 
                          <small class="text-muted">' . $row['latest_created_at'] . ' 
                          <a href="userprofile.php?id='.$girenadaminid.'" class="text-decoration-none" style="color: black;"> - '.$username.'</a>
                          </small>
                        </div>
                      </div>';
            }
        } else {
            echo "<p>Henüz bildiriminiz yok.</p>";
        }

        $stmt->close();
        $conn->close();
        ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>