<?php
// ini ayarları

ini_set('mysql.connect_timeout', 600); 
ini_set('default_socket_timeout', 600); 
ini_set('mysql.max_allowed_packet', '1024M'); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$dbservername = "cpanel1.kayizer.com"; 
$dbusername = "alibuyuk_root"; 
$dbpassword = "santamaria.31"; 
$dbdatabase = "alibuyuk_forum";
$port = 3306;

    $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $port);
?>