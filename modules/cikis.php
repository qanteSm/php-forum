<?php
session_start();

$_SESSION = array();

// Oturum cookielerini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

if (isset($_COOKIE['remember_me'])) {
    list($selector, $authenticator) = explode(':', $_COOKIE['remember_me']);

    require_once "mysqlconn.php";

    $sql = "DELETE FROM auth_tokens WHERE selector = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selector);
    $stmt->execute();

    setcookie('remember_me', '', time() - 3600, '/'); 
}

session_destroy();

header("Location: ../giris.php"); 
exit();
?>