<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        header("Location: giris.php?error=empty");
    } else {
        require_once "modules/mysqlconn.php";
        $sql = "SELECT * FROM accounts WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stored_hash = $row['pass'];
            $stored_salt = $row['salt'];
            $hashedenteredpw = md5($stored_salt . $password);
            if($stored_hash != $hashedenteredpw){
                header("Location: giris.php?error=wrongpass");
            }else{
                session_start();
                $_SESSION["entered"] = true;
                $_SESSION["id"] = $row['id'];

                if (isset($_POST['remember_me'])) {
                    $selector = base64_encode(random_bytes(9));
                    $authenticator = random_bytes(33);
                    $hashedAuthenticator = hash('sha256', $authenticator);
                    setcookie('remember_me', $selector . ':' . base64_encode($authenticator), time() + 86400 * 30, '/');

                    $sql = "INSERT INTO auth_tokens (selector, token, user_id) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $selector, $hashedAuthenticator, $row['id']);
                    $stmt->execute();
                }

                $yonlendirmeURL = isset($_POST['yonlendirme_url']) ? $_POST['yonlendirme_url'] : "index.php";

                header("Location: " . $yonlendirmeURL);
                exit();
            }
        } else {
            header("Location: giris.php?error=nouser");
        }

        exit();
    }
}
?>