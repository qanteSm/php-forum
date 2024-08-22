<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once "modules/mysqlconn.php";
require_once "modules/ranks.php";

if (!isset($_SESSION['entered']) || $_SESSION['entered'] !== true) {
    header("Location: giris.php");
    exit();
}

$userId = $_SESSION['id'];
$username = "";

$sql = "SELECT username FROM accounts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $username = htmlspecialchars($row['username']);
    $welcomeMessage = "Welcome, " . $username . "!";
    $statusbar = '
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
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
                        <a class="nav-link" href="userprofile.php?id=' . $userId . '">' . $username . '</a>
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = htmlspecialchars($_POST['baslik']);
    $icerik = htmlspecialchars($_POST['icerik']);
    $etiketler = htmlspecialchars($_POST['etiketler_dizi']);
    $secilenRank = isset($_POST['rank']) ? intval($_POST['rank']) : 0;

    
    function kullaniciAdiniBul($metin, $conn, $baslikreal,$postId) {
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
                    $baslikk = substr($baslikk, 0, 20) . "...";
                    }
                    $content .= "'$baslikk' başlıklı posta sizi etiketledi.";   
                    $link = "post.php?post=".$postId; 
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

    $hedefDizin = 'uploads/post-images/'; // Resimlerin yükleneceği dizin
    $resimYolu = ''; // Veritabanına kaydedilecek resim yolu

    if (isset($_FILES['resim']) && $_FILES['resim']['error'] === UPLOAD_ERR_OK) {
        $resimAdi = $_FILES['resim']['name'];
        $resimGeciciYolu = $_FILES['resim']['tmp_name'];

        
        $izinVerilenUzantılar = array('jpg', 'jpeg', 'png', 'gif');
        $resimUzantisi = strtolower(pathinfo($resimAdi, PATHINFO_EXTENSION));

        if (in_array($resimUzantisi, $izinVerilenUzantılar)) {
            $yeniResimAdi = uniqid('img_', true) . '.' . $resimUzantisi;
            $resimYolu = $hedefDizin . $yeniResimAdi;

            if (move_uploaded_file($resimGeciciYolu, $resimYolu)) {
                // Resim başarıyla yüklendi
            } else {
                $hataMesaji = "Resim yüklenirken bir hata oluştu.";
                $resimYolu = null; 
            }
        } else {
            $hataMesaji = "Geçersiz resim formatı. İzin verilen formatlar: JPG, JPEG, PNG, GIF";
        }
    } else if (isset($_FILES['resim']) && $_FILES['resim']['error'] !== UPLOAD_ERR_NO_FILE) {
        $hataMesaji = "Resim yüklenirken bir hata oluştu.";
        $resimYolu = null;
    }

    if (empty($baslik) || empty($icerik)) {
        $hataMesaji = "Başlık ve içerik alanları boş bırakılamaz.";
    } elseif (strlen($baslik) > 50) {
        $hataMesaji = "Başlık en fazla 50 karakter olabilir.";
    } elseif (strlen($icerik) > 500) {
        $hataMesaji = "İçerik en fazla 500 karakter olabilir.";
    } else {
        
        $etiketDizisi = explode(",", $etiketler);

        
        $sql = "INSERT INTO posts (baslik, icerik, etiketler, yazar, tarih, post_rank, resim) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $simdikiZaman = round(microtime(true) * 1000);

        if ($stmt === false) {
            echo "Hata: SQL sorgusu hazırlanamadı: " . $conn->error;
            exit;
        } else {
            if (!$hataMesaji){
                $stmt->bind_param("sssiiss", $baslik, $icerik, $etiketler, $userId, $simdikiZaman, $secilenRank, $resimYolu);

                if ($stmt->execute()) {
                    $yeniPostId = $conn->insert_id;
                    kullaniciAdiniBul($icerik,$conn,$baslik,$yeniPostId);
                    header("Location: post.php?post=" . $yeniPostId . "&status=success");
                    $basariMesaji = "Forum başarıyla oluşturuldu!";
                    $baslik = "";
                    $icerik = "";
                    $etiketler = "";
                } else {
                    $hataMesaji = "Forum oluşturulurken bir hata oluştu: " . $conn->error;
                }
            }
            
        }
    }
}
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
            <form action="postolustur.php" method="post" enctype="multipart/form-data"> 
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
                <div class="mb-3">
                    <label for="resim" class="form-label">Resim Ekle (İsteğe Bağlı):</label>
                    <input type="file" class="form-control" id="resim" name="resim">
                </div>

        <?php
            $userRankLevel = getRankLevelById($conn, $userId);
            if ($userRankLevel > 0) {
                echo '<div class="mb-3">';
                echo '<label for="rank" class="form-label">Post Rankı:</label>';
                echo '<select class="form-select" id="rank" name="rank">';
                echo '<option value="0">Herkese Açık</option>';

                $sql = "SELECT rank_level, rank_name FROM Ranks WHERE rank_level > 0";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row["rank_level"] . '">' . $row["rank_name"] . '</option>';
                    }
                }
                echo '</select>';
                echo '</div>';
            }
        ?>
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