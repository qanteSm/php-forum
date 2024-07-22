<?php
session_start();

if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
    header("Location: index.php");
    exit(); 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Kaydol - Forum</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style type="text/css">
    body {
        margin-top: 20px;
        background: #eee;
        color: #708090;
    }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.html">Forum</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link"  href="index.php">Ana Sayfa</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="giris.php">Giriş</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="kaydol.php">Kaydol</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card mt-5">
        <div class="card-body">
          <h3 class="card-title text-center">Kaydol</h3>

          <?php if(isset($_GET['error']) && $_GET['error'] == 'usernametaken'): ?>
            <div class="alert alert-danger" role="alert">
              Username zaten kullanılıyor. Lütfen farklı bir username seçin.
            </div>
          <?php endif; ?>

          <?php if(isset($_GET['error']) && $_GET['error'] == 'error'): ?>
            <div class="alert alert-danger" role="alert">
              Bir hata oluştu sonra dene.
            </div>
          <?php endif; ?>

          <?php if(isset($_GET['error']) && $_GET['error'] == 'empty'): ?>
            <div class="alert alert-danger" role="alert">
              Kullanıcı adın veya şifren boş olamaz eşşek.
            </div>
          <?php endif; ?>

          <?php if(isset($_GET['error']) && $_GET['error'] == 'rewrong'): ?>
            <div class="alert alert-danger" role="alert">
              Eşşek misin girdiğin şifreler eşleşmiyor.
            </div>
          <?php endif; ?>
            
          <?php if(isset($_GET['success']) && $_GET['success'] == 'true'): ?>
          <div class="alert alert-success" role="alert">
            Kayıt işlemi başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.
          </div>
          <?php endif; ?>
          <form action="kaydolcodes.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Kullanıcı Adı</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı adınızı giriniz">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Şifrenizi giriniz">
            </div>
            <div class="mb-3">
                <label for="passwordre" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="passwordre" name="passwordre" placeholder="Şifrenizi tekrar giriniz">
            </div>
            <button type="submit" class="btn btn-primary w-100">Kaydol</button>
          </form>
          
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
