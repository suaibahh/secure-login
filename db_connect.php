<?php
// Ambil variabel dari Vercel
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$database = getenv('DB_NAME');
$port = (int)getenv('DB_PORT');


$conn = mysqli_init();

mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

if (!mysqli_real_connect($conn, $servername, $username, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL)) {
 
    die("Connection failed: " . mysqli_connect_error());
}


$conn->set_charset("utf8mb4");


?>