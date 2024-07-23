<?php
session_start();

require_once "modules/mysqlconn.php";
global $conn;
require_once "modules/ranks.php";
header('Content-Type: text/html; charset=utf-8'); 
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
}else {
    $userId = null; 
}

$targetUsername = isset($_GET['user']) ? $_GET['user'] : '';
$targetUserId = isset($_GET['id']) ? $_GET['id'] : '';

$statusbar = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Forum</a>
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
                        <a class="nav-link" href="userprofile.php?id='.$_SESSION['id'].'">' . $username . '</a>
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

if (!empty($targetUsername)) {
    $sql = "SELECT * FROM accounts WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $targetUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (isset($_SESSION['entered']) && $_SESSION['entered'] === true && $_SESSION['id'] != $row['id']) {
            $visitTime = round(microtime(true) * 1000);
            $sql = "INSERT INTO user_visits (visitor_id, visited_user_id, visit_time) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $userId, $row['id'], $visitTime);
            $stmt->execute();
            $stmt->close();
        }
        $profileContent = getUserProfileContent($row, $userId, $conn); 
    } else {
        $profileContent = '<div class="container py-5">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>Kullanıcı Bulunamadı</h1>
                    <p>Girilen kullanıcı adı ile eşleşen bir kullanıcı bulunamadı.</p>
                </div>
            </div>
        </div>';
    }
    $stmt->close();
} elseif (!empty($targetUserId)) {
    $sql = "SELECT * FROM accounts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (isset($_SESSION['entered']) && $_SESSION['entered'] === true && $_SESSION['id'] != $row['id']) {
            $visitTime = round(microtime(true) * 1000);
            $sql = "INSERT INTO user_visits (visitor_id, visited_user_id, visit_time) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $userId, $row['id'], $visitTime);
            $stmt->execute();
            $stmt->close();
        }
        $profileContent = getUserProfileContent($row, $userId, $conn); 
    } else {
        $profileContent = '<div class="container py-5">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>Kullanıcı Bulunamadı</h1>
                    <p>Girilen ID ile eşleşen bir kullanıcı bulunamadı.</p>
                </div>
            </div>
        </div>';
    }
} else {
    $profileContent = '<div class="container py-5">
        <div class="row">
            <div class="col-md-12 text-center">
                <h1>Kullanıcı Seçin</h1>
                <p>Bir kullanıcı profili görüntülemek için URLye kullanıcı adını veya IDsini ekleyin (örneğin: userprofile.php?user=deneme veya userprofile.php?id=1).</p>
            </div>
        </div>
    </div>';
}

function getUserProfileContent($row, $userId, $conn) {
    global $recentVisitsHTML;
    $profileButton = '';
    $followButton = '<button  type="button" data-mdb-button-init data-mdb-ripple-init class="btn btn-primary">Takip Et</button>';
    $messageButton = '<button  type="button" data-mdb-button-init data-mdb-ripple-init class="btn btn-outline-primary ms-1">Mesaj</button>';
    $postsButton = '<a href="tumpostlar.php?fromuser=' . $row['id'] . '" class="btn btn-outline-secondary ms-1">Gönderiler</a>';

    if ($userId == $row['id']) {
        $profileButton = '<a href="profil.php" class="btn btn-primary">Profili Özelleştir</a>';
        $followButton = '';
        $messageButton = '';
    }

    $rankName = getRankNameById($conn, $row['id']);

    $sql = "SELECT v.visit_time, a.username, a.profil_fotografi, v.visitor_id 
        FROM user_visits v
        JOIN accounts a ON v.visitor_id = a.id
        WHERE v.visited_user_id = ?
        GROUP BY v.visitor_id
        ORDER BY MAX(v.visit_time) DESC
        LIMIT 4";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $row['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $recentVisits = [];
    $visitedUserIds = [];
    while ($visitRow = $result->fetch_assoc()) {
        if (!in_array($visitRow['visitor_id'], $visitedUserIds)) {
            $recentVisits[] = $visitRow;
            $visitedUserIds[] = $visitRow['visitor_id'];
        }
    }
    $stmt->close();

    $recentVisitsHTML = '<div class="bg-light rounded-3 p-3 mt-3">';

    if (!empty($recentVisits)) {
        $recentVisitsHTML .= '<p class="mb-0">Son Ziyaret Edenler</p>';
        $recentVisitsHTML .= '<div class="d-flex flex-wrap">'; 

        foreach ($recentVisits as $visit) {
            $visitorSql = "SELECT username, profil_fotografi FROM accounts WHERE id = ?";
            $visitorStmt = $conn->prepare($visitorSql);
            $visitorStmt->bind_param("i", $visit['visitor_id']);
            $visitorStmt->execute();
            $visitorResult = $visitorStmt->get_result();
            $visitorRow = $visitorResult->fetch_assoc();
            $visitorStmt->close();

            $profileLink = "userprofile.php?id=" . $visit['visitor_id'];
            $recentVisitsHTML .= '<div class="d-flex align-items-center me-3 mb-2 bg-secondary rounded-pill px-3 py-2"> 
                                    <a href="' . $profileLink . '">
                                        <img src="' . 'uploads/profil-fotograflari/' . (!empty($visitorRow['profil_fotografi']) ? $visitorRow['profil_fotografi'] : 'fotoyok.jpg') . '" alt="avatar" class="rounded-circle img-fluid" style="width: 30px;">
                                    </a>
                                    <div class="ms-2 text-white">
                                        <a href="' . $profileLink . '" class="text-white">' . $visitorRow['username'] . '</a>
                                    </div>
                                </div>'; 
        }

        $recentVisitsHTML .= '</div>'; 
    } else {
        $recentVisitsHTML .= '<p class="mb-0">Henüz hiç ziyaretçi yok.</p>'; 
    }

    $recentVisitsHTML .= '</div>';

    return '<section style="background-color: #eee;">
          <div class="container py-5">
            <div class="row">
              <div class="col">
                <nav aria-label="breadcrumb" class="bg-body-tertiary rounded-3 p-3 mb-4">
                  <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active" aria-current="page">Kullanıcı Profili</li>
                  </ol>
                </nav>
              </div>
            </div>

            <div class="row">
              <div class="col-lg-4">
                <div class="card mb-4">
                  <div class="card-body text-center">
                    <img src="' . 'uploads/profil-fotograflari/' . (!empty($row['profil_fotografi']) ? $row['profil_fotografi'] : 'fotoyok.jpg') . '" alt="avatar"
                      class="rounded-circle img-fluid" style="width: 150px;">
                    <h5 class="my-3">' . $row['username'] . '</h5>
                    <p class="text-muted mb-1">' . htmlspecialchars($rankName) . '</p>  <!-- Rütbe bilgisini burada göster -->
                    <div class="d-flex justify-content-center mb-2">
                      ' . $followButton . '
                      ' . $messageButton . '
                      ' . $postsButton . '
                    </div>
                    ' . $profileButton . '
                  </div>
                </div>
              </div>
              <div class="col-lg-8">
                <div class="card mb-4">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-sm-12">
                      <p class="mb-0">Açıklama</p>
                        <div class="bg-light rounded-3 p-3">
                          
                          <p class="text-muted mb-0">
                            ' . $row['description'] . ' 
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                ' . $recentVisitsHTML . '
              </div>
            </div>
          </div>
        </section>';
}

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
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>

<?php echo $statusbar; ?> 

<?php echo $profileContent; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>