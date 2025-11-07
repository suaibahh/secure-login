<?php
// 1. Sertakan koneksi DB dan handler session
// KONEKSI DB DIPERLUKAN untuk membaca session
include "db_connect_cloud.php"; // <-- PENTING: Gunakan file koneksi cloud
include "session_handler.php";  // <-- PENTING: Sertakan handler

// 2. Inisialisasi handler dengan koneksi database
$handler = new MySQLSessionHandler($conn);

// 3. Set save handler kustom
session_set_save_handler($handler, true);

// Session hardening
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// 4. Mulai session
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

// Cek session timeout (30 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update last activity
$_SESSION['last_activity'] = time();

// Regenerate session ID setiap 5 menit untuk keamanan
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 300) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

$username = $_SESSION['username'];


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .welcome-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 32px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Dashboard</h1>
        <div class="user-info">
            <span>Halo, <?php echo htmlspecialchars($username); ?>!</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Selamat Datang!</h2>
            <p>Anda telah berhasil login ke sistem. Ini adalah halaman dashboard Anda.</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>✓</h3>
                <p>Login Berhasil</p>
            </div>
            <div class="stat-card">
                <h3>🔒</h3>
                <p>Akun Aman</p>
            </div>
            <div class="stat-card">
                <h3>🚀</h3>
                <p>Siap Digunakan</p>
            </div>
        </div>
    </div>
</body>
</html>