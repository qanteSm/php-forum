<?php
session_start();

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    header("Location: index.php");
    exit();
} else {
    $yonlendirmeURL = isset($_GET['yonlendirme_url']) ? htmlspecialchars($_GET['yonlendirme_url']) : "index.php";

    if (isset($_COOKIE['remember_me'])) {
        list($selector, $authenticator) = explode(':', $_COOKIE['remember_me']);

        require_once "modules/mysqlconn.php";
        $sql = "SELECT * FROM auth_tokens WHERE selector = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (hash_equals($row['token'], hash('sha256', base64_decode($authenticator)))) {
                $sql = "SELECT * FROM accounts WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $row['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $_SESSION["entered"] = true;
                    $_SESSION["id"] = $user['id'];

                    header("Location: " . $yonlendirmeURL);
                    exit();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Giriş Yap - Forum</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style type="text/css">
    body {
        margin-top: 20px;
        background: #eee;
        color: #708090;
        display: flex;
        flex-direction: column;
        min-height: 100vh; 
        margin: 0px;
    }

    .main-content { 
        flex: 1; 
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .form {
        display: flex;
        flex-direction: column;
        gap: 10px;
        background-color: #ffffff;
        padding: 30px;
        width: 450px;
        border-radius: 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

::placeholder {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

.form button {
  align-self: flex-end;
}

.flex-column > label {
  color: #151717;
  font-weight: 600;
}

.inputForm {
  border: 1.5px solid #ecedec;
  border-radius: 10px;
  height: 50px;
  display: flex;
  align-items: center;
  padding-left: 10px;
  transition: 0.2s ease-in-out;
}

.input {
  margin-left: 10px;
  border-radius: 10px;
  border: none;
  width: 100%;
  height: 100%;
}

.input:focus {
  outline: none;
}

.inputForm:focus-within {
  border: 1.5px solid #2d79f3;
}

.flex-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10px;
  justify-content: space-between;
}

.flex-row > div > label {
  font-size: 14px;
  color: black;
  font-weight: 400;
}

.span {
  font-size: 14px;
  margin-left: 5px;
  color: #2d79f3;
  font-weight: 500;
  cursor: pointer;
}

.button-submit {
  margin: 20px 0 10px 0;
  background-color: #151717;
  border: none;
  color: white;
  font-size: 15px;
  font-weight: 500;
  border-radius: 10px;
  height: 50px;
  width: 100%;
  cursor: pointer;
}

.p {
  text-align: center;
  color: black;
  font-size: 14px;
  margin: 5px 0;
}

.btn {
  margin-top: 10px;
  width: 100%;
  height: 50px;
  border-radius: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: 500;
  gap: 10px;
  border: 1px solid #ededef;
  background-color: white;
  cursor: pointer;
  transition: 0.2s ease-in-out;
}

.btn:hover {
  border: 1px solid #2d79f3;
  ;
}

</style>
</head>
<body>
<link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet">

<!-- Üst Menü Başlangıcı -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="main.html">Forum</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link"  href="index.php">Ana Sayfa</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="giris.php">Giriş</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="kaydol.php">Kaydol</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="main-content">
  
  <form action="giriscodes.php" method="POST" class="form">
  <?php if(isset($_GET['error']) && $_GET['error'] == 'empty'): ?>
            <div class="alert alert-danger" role="alert">
              Kullanıcı adın veya şifren boş olamaz eşşek.
            </div>
          <?php endif; ?>
          <?php if(isset($_GET['error']) && $_GET['error'] == 'error'): ?>
            <div class="alert alert-danger" role="alert">
              Bir hata oluştu daha sonra tekrar deneyiniz.
            </div>
          <?php endif; ?>
          <?php if(isset($_GET['error']) && $_GET['error'] == 'nouser'): ?>
            <div class="alert alert-danger" role="alert">
              Böyle bir kullanıcı bulunamadı adam gibi username gir eşşek.
            </div>
          <?php endif; ?>
          <?php if(isset($_GET['error']) && $_GET['error'] == 'wrongpass'): ?>
            <div class="alert alert-danger" role="alert">
              Şifren yanlış yoksa hesap mı çalmaya çalışıyon küçük eşşek?
            </div>
          <?php endif; ?>


          <?php if(isset($_GET['error']) && $_GET['error'] == 'needaccount'): ?>
            <div class="alert alert-danger" role="alert">
              Giriş yapman gerek!
            </div>
          <?php endif; ?>

          <?php if(isset($_GET['success']) && $_GET['success'] == 'true'): ?>
          <div class="alert alert-success" role="alert">
            Giriş başarılı yönlendiriliyorsun...
          </div>
          <?php endif; ?>
  <input type="hidden" name="yonlendirme_url" value="<?php echo $yonlendirmeURL; ?>">
    <div class="flex-column">
      <label>Kullanıcı Adı </label></div>
      <div class="inputForm">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <circle cx="12" cy="6" r="4" stroke="#000000" stroke-width="1.5"></circle> <path d="M19.9975 18C20 17.8358 20 17.669 20 17.5C20 15.0147 16.4183 13 12 13C7.58172 13 4 15.0147 4 17.5C4 19.9853 4 22 12 22C14.231 22 15.8398 21.8433 17 21.5634" stroke="#000000" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
        <input placeholder="Kullanıcı Adınızı Giriniz" class="input" type="text" id="username" name="username">
      </div>
    
    <div class="flex-column">
      <label>Şifre </label></div>
      <div class="inputForm">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" viewBox="-64 0 512 512" height="20"><path d="m336 512h-288c-26.453125 0-48-21.523438-48-48v-224c0-26.476562 21.546875-48 48-48h288c26.453125 0 48 21.523438 48 48v224c0 26.476562-21.546875 48-48 48zm-288-288c-8.8125 0-16 7.167969-16 16v224c0 8.832031 7.1875 16 16 16h288c8.8125 0 16-7.167969 16-16v-224c0-8.832031-7.1875-16-16-16zm0 0"></path><path d="m304 224c-8.832031 0-16-7.167969-16-16v-80c0-52.929688-43.070312-96-96-96s-96 43.070312-96 96v80c0 8.832031-7.167969 16-16 16s-16-7.167969-16-16v-80c0-70.59375 57.40625-128 128-128s128 57.40625 128 128v80c0 8.832031-7.167969 16-16 16zm0 0"></path></svg>        
        <input placeholder="Şifrenizi Giriniz" class="input" type="password" id="password" name="password">
      </div>
    
      <div class="flex-row">
      <div>
        <input type="checkbox" id="rememberMe" name="remember_me"> <label for="rememberMe">Beni Hatırla</label>
      </div>
    </div>
    <button class="button-submit">Giriş Yap</button>
      <p class="p">Hesabın Yok Mu? <a href="kaydol.php" class="span">Kayıt Ol</a>
   </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>