<?php
// create_sessions_table.php
// Ganti ini ke file koneksi cloud Anda
include "db_connect_cloud.php"; 

if (!$conn) {
    die("Koneksi gagal. Periksa db_connect_cloud.php");
}

$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    data TEXT,
    last_accessed INT NOT NULL,
    INDEX idx_last_accessed (last_accessed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ Tabel 'sessions' berhasil dibuat atau sudah ada!";
} else {
    echo "❌ Error membuat tabel 'sessions': " . $conn->error;
}

$conn->close();
?>