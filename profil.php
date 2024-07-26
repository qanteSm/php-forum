<?php
session_start();

require_once "modules/mysqlconn.php";

if (!isset($_SESSION['entered']) || $_SESSION['entered'] !== true) {
    header("Location: giris.php"); 
    exit();
}

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    $userId = $_SESSION['id'];

    require_once "modules/mysqlconn.php";
    $sql = "SELECT username, description FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username = htmlspecialchars($row['username']);
        $description = htmlspecialchars($row['description']);
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
}

$userId = $_SESSION['id'];
$profilFotosu = "fotoyok.jpg"; 

$sql = "SELECT profil_fotografi FROM accounts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!empty($row['profil_fotografi'])) {
        $profilFotosu = $row['profil_fotografi'];
    }
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profilFotografi'])) {
    $hedefDizin = "uploads/profil-fotograflari/"; 
    $izinVerilenUzantılar = array("gif", "png", "jpg", "jpeg");

    $dosyaAdi = basename($_FILES["profilFotografi"]["name"]);
    $dosyaUzantisi = strtolower(pathinfo($dosyaAdi, PATHINFO_EXTENSION));
    $yeniDosyaAdi = $userId . "_" . time() . "." . $dosyaUzantisi;
    $hedefDosya = $hedefDizin . $yeniDosyaAdi;

    $yuklemeBasarili = true;

    if (!in_array($dosyaUzantisi, $izinVerilenUzantılar)) {
        $yuklemeHatasi = "Sadece GIF, PNG, JPG ve JPEG dosyaları yükleyebilirsiniz.";
        $yuklemeBasarili = false;
    }

    if ($yuklemeBasarili && move_uploaded_file($_FILES["profilFotografi"]["tmp_name"], $hedefDosya)) {
        $sql = "UPDATE accounts SET profil_fotografi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $yeniDosyaAdi, $userId);

        if ($stmt->execute()) {
            $profilFotosu = $yeniDosyaAdi;
            $yuklemeMesaji = "Profil fotoğrafınız başarıyla yüklendi.";
        } else {
            $yuklemeHatasi = "Yükleme sırasında bir hata oluştu: " . $conn->error;
        }

        $stmt->close();
    } else {
        $yuklemeHatasi = "Dosya yüklenirken bir hata oluştu.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['description'])) {
    $yeniAciklama = trim($_POST['description']);

    if (strlen($yeniAciklama) <= 250) {
        $sql = "UPDATE accounts SET description = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $yeniAciklama, $userId);

        if ($stmt->execute()) {
            $description = $yeniAciklama;
            $aciklamaGuncellemeMesaji = "Profil açıklamanız başarıyla güncellendi.";
        } else {
            $aciklamaGuncellemeHatasi = "Açıklama güncellenirken bir hata oluştu: " . $conn->error;
        }
        $stmt->close();
    } else {
        $aciklamaGuncellemeHatasi = "Açıklama 250 karakterden fazla olamaz.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Ayarları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #eee;
            color: #708090;
        }
        .profil-fotografi {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
        .ayarlar-karti {
            border-radius: 15px; 
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }
    </style>
</head>
<body>
    <?php echo $statusbar; ?>
    <div class="container mt-5">
        <h2>Profil Ayarları</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="ayarlar-karti">
                    <img src="<?php echo "uploads/profil-fotograflari/" . $profilFotosu; ?>" alt="Profil Fotoğrafı" class="profil-fotografi">
                    <br>
                    <form action="profil.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="profilFotografi" id="profilFotografi" class="form-control mt-3">
                        <button type="submit" class="btn btn-primary mt-2">Resmi Değiştir</button>
                    </form>
                    <?php 
                        if(isset($yuklemeHatasi)) echo '<p style="color:red;">'.$yuklemeHatasi.'</p>';
                        if(isset($yuklemeMesaji)) echo '<p style="color:green;">'.$yuklemeMesaji.'</p>';
                    ?> 
                </div>
            </div>
            <div class="col-md-8">
                <div class="ayarlar-karti">
                    <h3>Profil Açıklaması</h3>
                    <form action="profil.php" method="post">
                        <textarea name="description" id="description" class="form-control" rows="4"><?php echo $description; ?></textarea>
                        <button type="submit" class="btn btn-primary mt-2">Güncelle</button>
                    </form>
                    <?php 
                        if(isset($aciklamaGuncellemeHatasi)) echo '<p style="color:red;">'.$aciklamaGuncellemeHatasi.'</p>';
                        if(isset($aciklamaGuncellemeMesaji)) echo '<p style="color:green;">'.$aciklamaGuncellemeMesaji.'</p>';
                    ?> 
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>