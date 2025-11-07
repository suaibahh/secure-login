<?php
// 1. Sertakan koneksi DB dan handler session
include "db_connect_cloud.php"; // <-- PENTING: Gunakan file koneksi cloud
include "session_handler.php";  // <-- PENTING: Sertakan handler

// 2. Inisialisasi handler dengan koneksi database
$handler = new MySQLSessionHandler($conn);

// 3. Set save handler kustom
session_set_save_handler($handler, true);

// Session hardening (set sebelum session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// 4. Mulai session
session_start();

// Konfigurasi reCAPTCHA
$RECAPTCHA_SITEKEY = getenv('RECAPTCHA_SITEKEY');
$RECAPTCHA_SECRET = getenv('RECAPTCHA_SECRET');

// Fungsi bantu: dapatkan IP client
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

if (isset($_POST['register'])) {
    // Ambil dan sanitasi input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input kosong
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        echo "<script>alert('Harap isi semua field.'); window.location='register.php';</script>";
        exit;
    }

    // Validasi format email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format email tidak valid.'); window.location='register.php';</script>";
        exit;
    }

    // Validasi panjang password
    if (strlen($password) < 8) {
        echo "<script>alert('Password minimal 8 karakter.'); window.location='register.php';</script>";
        exit;
    }

    // Cek password cocok
    if ($password !== $confirm_password) {
        echo "<script>alert('Password tidak cocok!'); window.location='register.php';</script>";
        exit;
    }

    // Validasi reCAPTCHA
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    if (!$captcha) {
        echo "<script>alert('Silakan centang reCAPTCHA terlebih dahulu.'); window.location='register.php';</script>";
        exit;
    }

    $ip = get_client_ip();
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($RECAPTCHA_SECRET) . "&response=" . urlencode($captcha) . "&remoteip=" . urlencode($ip));
    $resp = json_decode($verify);
    
    if (!$resp || !$resp->success) {
        echo "<script>alert('Verifikasi reCAPTCHA gagal.'); window.location='register.php';</script>";
        exit;
    }

    // Cek username sudah digunakan
    $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result_username = $check_username->get_result();

    if ($result_username->num_rows > 0) {
        $check_username->close();
        echo "<script>alert('Username sudah digunakan!'); window.location='register.php';</script>";
        exit;
    }
    $check_username->close();

    // Cek email sudah digunakan
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result_email = $check_email->get_result();

    if ($result_email->num_rows > 0) {
        $check_email->close();
        echo "<script>alert('Email sudah terdaftar!'); window.location='register.php';</script>";
        exit;
    }
    $check_email->close();

    // Hash password dan insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $insert->bind_param("sss", $username, $hashed_password, $email);

    if ($insert->execute()) {
        $insert->close();
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location.href='login.php';</script>";
        exit;
    } else {
        $insert->close();
        echo "<script>alert('Registrasi gagal! Silakan coba lagi.'); window.location='register.php';</script>";
        exit;
    }
}

// Tutup koneksi DB di akhir script
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi</title>

    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        * { box-sizing: border-box; margin:0; padding:0; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .card { background: #fff; padding: 36px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); width:100%; max-width:420px; }
        h2 { text-align:center; margin-bottom:18px; color:#333; }
        .form-group { margin-bottom:16px; }
        label { display:block; margin-bottom:6px; color:#555; font-weight:600; }
        input[type="text"], input[type="email"], input[type="password"] { width:100%; padding:10px 12px; border:1.5px solid #ddd; border-radius:6px; font-size:14px; }
        input:focus { outline:none; border-color:#667eea; }
        .btn { width:100%; padding:12px; margin-top:12px; background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border:none; border-radius:6px; font-weight:700; cursor:pointer; }
        .btn:hover { opacity:0.9; }
        .small { text-align:center; margin-top:12px; color:#666; }
        a { color:#667eea; text-decoration:none; font-weight:700; }
        .g-recaptcha { margin: 12px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Daftar Akun Baru</h2>
        <form action="" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" placeholder="Masukkan username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="nama@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Minimal 8 karakter" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input id="confirm_password" type="password" name="confirm_password" placeholder="Ulangi password" required>
            </div>

            <!-- reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($RECAPTCHA_SITEKEY); ?>"></div>

            <button classB="btn" type="submit" name="register">Daftar</button>
        </form>

        <div class="small">
            Sudah punya akun? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>