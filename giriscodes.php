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
                if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
                    header("Location: main.php");
                    exit(); 
                } else {
                    header("Location: giris.php?success=true");
                    session_start();
                    $_SESSION["entered"] = true;
                    $_SESSION["id"] = $row['id'];
                    header("Location: main.php");
                }
            }
        } else {
            header("Location: giris.php?error=nouser");
        }
       
        exit();
    }
}
?>
