<?php
// Ambil variabel dari Vercel
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$database = getenv('DB_NAME');
$port = (int)getenv('DB_PORT');

// Tentukan path ke CA certificate
// __DIR__ adalah konstanta PHP yang menunjuk ke direktori file saat ini.
$ca_path = __DIR__ . '/ca.pem'; // Pastikan 'ca.pem' ada di root

$conn = mysqli_init();

// Periksa apakah file CA ada
if (!file_exists($ca_path)) {
    die("Connection failed: CA certificate file not found at " . $ca_path);
}

// Set opsi SSL
mysqli_ssl_set($conn, NULL, NULL, $ca_path, NULL, NULL);

// Hubungkan menggunakan SSL
if (!mysqli_real_connect($conn, $servername, $username, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error() . " (SSL Error: " . mysqli_error($conn) . ")");
}

$conn->set_charset("utf8mb4");

?>