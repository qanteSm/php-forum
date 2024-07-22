<?php
$dbservername = "localhost"; 
$dbusername = "root"; 
$dbpassword = ""; 
$dbdatabase = "forum";

$conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase);

if ($conn->connect_error) {
    die("MySQL bağlantısı başarısız: " . $conn->connect_error);
}
?>