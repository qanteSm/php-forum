<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $passwordre = $_POST["passwordre"];
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    if (empty($username) || empty($password)) {
        header("Location: kaydol.php?error=empty");
    } else {
        if($passwordre != $password){
            header("Location: kaydol.php?error=rewrong");
        }else {
            require_once "modules/mysqlconn.php";
            $sql = "SELECT * FROM accounts WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                header("Location: kaydol.php?error=usernametaken");
            } else {
                $salt = generateRandomString(10);
                $hashedpw = md5($salt . $password);
                $sql = "INSERT INTO accounts (username, pass,salt,rank,profil_fotografi) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $foto = "fotoyok.jpg";
                $rank = 0;
                $stmt->bind_param("sssis", $username, $hashedpw, $salt, $rank,$foto);
                if ($stmt->execute()) {
                    header("Location: kaydol.php?success=true");
                } else {
                    header("Location: kaydol.php?error=error");
                }
            }
        }
        
       
        exit();
    }
}
?>
