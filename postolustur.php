<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once "modules/mysqlconn.php";

if (!isset($_SESSION['entered']) || $_SESSION['entered'] !== true) {
    header("Location: giris.php"); 
    exit();
}
    $userId = $_SESSION['id'];
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
                <a class="nav-link active" href="main.php">Ana Sayfa</a>
              </li>              <li class="nav-item">
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
    die("Kullanıcı bulunamadı."); 
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = htmlspecialchars($_POST['baslik']);
    $icerik = htmlspecialchars($_POST['icerik']);
    $etiketler = htmlspecialchars($_POST['etiketler_dizi']);

    if (empty($baslik) || empty($icerik)) {
        $hataMesaji = "Başlık ve içerik alanları boş bırakılamaz.";
    } elseif (strlen($baslik) > 50) {
        $hataMesaji = "Başlık en fazla 50 karakter olabilir.";
    } elseif (strlen($icerik) > 200) {
        $hataMesaji = "İçerik en fazla 200 karakter olabilir.";
    } else {

    $etiketDizisi = explode(",", $etiketler);

    $sql = "INSERT INTO posts (baslik, icerik, etiketler, yazar, tarih) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $simdikiZaman = round(microtime(true) * 1000);

    if ($stmt === false) {
        echo "Hata: SQL sorgusu hazırlanamadı: " . $conn->error;
        exit;
    } else {
        $stmt->bind_param("sssii", $baslik, $icerik, $etiketler, $userId, $simdikiZaman);

        if ($stmt->execute()) {
            $basariMesaji = "Forum başarıyla oluşturuldu! Etiketler: ";
            $baslik = "";
            $icerik = "";
            $etiketler = "";
        } else {
            $hataMesaji = "Forum oluşturulurken bir hata oluştu: " . $conn->error;
        }
        $stmt->close();
    }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forum Oluştur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .etiket {
            background-color: #f0f0f5;
            border-radius: 10px;
            padding: 5px 10px;
            margin-right: 10px; /* Etiketler arası boşluk */
            margin-bottom: 5px;
            display: inline-block;
        }

        .form-karti {
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        #etiketler-container {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px; /* Etiketler ile girdi alanı arası boşluk */
        }
    </style>
</head>
<body>
<?php echo $statusbar; ?>
    <div class="container mt-5">
        <h2>Yeni Forum Oluştur</h2>

        <?php 
            if (isset($hataMesaji)) echo '<div class="alert alert-danger">' . $hataMesaji . '</div>';
            if (isset($basariMesaji)) echo '<div class="alert alert-success">' . $basariMesaji . '</div>';
        ?>

        <div class="form-karti"> 
            <form action="postolustur.php" method="post"> 
                <div class="mb-3">
                    <label for="baslik" class="form-label">Başlık:</label>
                    <input type="text" class="form-control" id="baslik" name="baslik" maxlength="50" value="<?php echo isset($baslik) ? $baslik : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="icerik" class="form-label">Açıklama:</label>
                    <textarea class="form-control" id="icerik" name="icerik" maxlength="200"><?php echo isset($icerik) ? $icerik : ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="etiketler" class="form-label">Etiketler (boşlukla ayırın, max 5 etiket):</label>
                    <input type="text" class="form-control" id="etiketler" name="etiketler" value="<?php echo isset($etiketler) ? $etiketler : ''; ?>">
                    <div id="etiketler-container"></div>
                </div>
                <button type="submit" class="btn btn-primary">Forum Oluştur</button>
            </form>
        </div> 
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const etiketlerInput = document.getElementById('etiketler');
        const etiketlerContainer = document.getElementById('etiketler-container');
        const maxEtiketSayisi = 5;
        let etiketlerDizisi = [];

        etiketlerInput.addEventListener('keyup', function(e) {
            if (e.key === ' ') {
                const etiketler = this.value.trim().split(' ');
                const etiketSayisi = etiketlerContainer.children.length;

                if (etiketSayisi < maxEtiketSayisi && etiketler[etiketler.length - 1] !== '') {
                    const etiketElementi = document.createElement('span');
                    etiketElementi.classList.add('etiket');
                    etiketElementi.textContent = etiketler[etiketler.length - 1];
                    etiketlerContainer.appendChild(etiketElementi);

                    etiketlerDizisi.push(etiketler[etiketler.length - 1]);
                    this.value = '';
                }
            }
        });

        document.querySelector('form').addEventListener('submit', function() {
            const hiddenEtiketlerInput = document.createElement('input');
            hiddenEtiketlerInput.setAttribute('type', 'hidden');
            hiddenEtiketlerInput.setAttribute('name', 'etiketler_dizi');
            hiddenEtiketlerInput.setAttribute('value', etiketlerDizisi.join(','));
            this.appendChild(hiddenEtiketlerInput);
        });
    </script>
</body>
</html>