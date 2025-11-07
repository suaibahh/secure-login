<?php
// Ambil variabel dari Vercel
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$database = getenv('DB_NAME');
$port = (int)getenv('DB_PORT');

// Tentukan path ke CA certificate
// Ini mengasumsikan file 'ca.pem' ada di direktori yang sama dengan file PHP ini.
// __DIR__ adalah konstanta PHP yang menunjuk ke direktori file saat ini.
$ca_path = __DIR__ . '/ca.pem';

$conn = mysqli_init();

// Periksa apakah file CA ada sebelum mencoba menggunakannya
if (!file_exists($ca_path)) {
    die("Connection failed: CA certificate file not found at " . $ca_path);
}

// Set opsi SSL untuk verifikasi menggunakan CA certificate
// Kita mengganti mysqli_options dengan mysqli_ssl_set
// Parameter: (koneksi, key_file, cert_file, ca_file, ca_path, cipher_algos)
mysqli_ssl_set($conn, NULL, NULL, $ca_path, NULL, NULL);

// Sekarang, hubungkan menggunakan SSL
// Flag MYSQLI_CLIENT_SSL memberitahu klien untuk mengaktifkan SSL
if (!mysqli_real_connect($conn, $servername, $username, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL)) {
    // Berikan pesan error yang lebih jelas jika koneksi gagal
    die("Connection failed: " . mysqli_connect_error() . " (SSL Error: " . mysqli_error($conn) . ")");
}

$conn->set_charset("utf8mb4");

?>