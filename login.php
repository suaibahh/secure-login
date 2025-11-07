<?php
// login.php (updated)

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
// ini_set('session.cookie_secure', 1); // Aktifkan ini jika sudah full HTTPS

// 4. Mulai session
session_start();

// Konfigurasi reCAPTCHA - GANTI dengan keys milikmu
$RECAPTCHA_SITEKEY = getenv('RECAPTCHA_SITEKEY');
$RECAPTCHA_SECRET = getenv('RECAPTCHA_SECRET');

// Fungsi bantu: dapatkan IP client (sederhana)
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Rate limiting: max gagal dalam window (mis. 5 kali dalam 15 menit)
define('MAX_ATTEMPTS', 5);
define('ATTEMPT_WINDOW_MINUTES', 15);

if (isset($_POST['login'])) {
    // Ambil input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // cek input kosong
    if (empty($email) || empty($password)) {
        echo "<script>alert('Harap isi email dan password.'); window.location='login.php';</script>";
        exit;
    }

    // reCAPTCHA server-side validation
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    if (!$captcha) {
        echo "<script>alert('Silakan centang reCAPTCHA terlebih dahulu.'); window.location='login.php';</script>";
        exit;
    }

    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($RECAPTCHA_SECRET) . "&response=" . urlencode($captcha) . "&remoteip=" . urlencode(get_client_ip()));
    $resp = json_decode($verify);
    if (!$resp || !$resp->success) {
        // Jika perlu, cek $resp->{"error-codes"} untuk debugging
        echo "<script>alert('Verifikasi reCAPTCHA gagal.'); window.location='login.php';</script>";
        exit;
    }

    // Rate limiting check (cek percobaan gagal berdasarkan email atau IP)
    $ip = get_client_ip();
    $minutes = ATTEMPT_WINDOW_MINUTES;

    // Hitung jumlah percobaan gagal untuk email dalam window
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE (email = ? OR ip_address = ?) AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE) AND success = 0");
    $stmt->bind_param("ssi", $email, $ip, $minutes);
    $stmt->execute();
    $result_attempts = $stmt->get_result();
    $attempts_data = $result_attempts->fetch_assoc();
    $attempts = $attempts_data['attempts'] ?? 0;
    $stmt->close(); // Tutup statement sebelum membuat yang baru
    
    if ($attempts >= MAX_ATTEMPTS) {
        echo "<script>alert('Terlalu banyak percobaan login gagal. Coba lagi nanti.'); window.location='login.php';</script>";
        exit;
    }

    // Ambil user berdasarkan email
    $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $login_success = false;
    $user_id = null;
    $username = null;

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $username = $user['username'];
        $hash = $user['password'];

        if (password_verify($password, $hash)) {
            $login_success = true;
        }
    }
    $stmt->close(); // Tutup statement setelah selesai digunakan

    // Log login attempt
    $success_flag = $login_success ? 1 : 0;
    $stmt_ins = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)");
    $stmt_ins->bind_param("ssi", $email, $ip, $success_flag);
    $stmt_ins->execute();
    $stmt_ins->close();

    if ($login_success) {
        // Regenerate session id untuk mencegah fixation
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();

        // Redirect ke dashboard (ganti jika nama file berbeda)
        header("Location: dashboard.php");
        exit;
    } else {
        // Gagal login
        echo "<script>alert('Email atau password salah.'); window.location='login.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>

    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        * { box-sizing: border-box; margin:0; padding:0; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg,#ff9a9e 0%,#fad0c4 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .card { background: #fff; padding: 36px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); width:100%; max-width:420px; }
        h2 { text-align:center; margin-bottom:18px; color:#333; }
        .form-group { margin-bottom:16px; }
        label { display:block; margin-bottom:6px; color:#555; font-weight:600; }
        input[type="email"], input[type="password"] { width:100%; padding:10px 12px; border:1.5px solid #ddd; border-radius:6px; font-size:14px; }
        input:focus { outline:none; border-color:#ff7a7a; }
        .btn { width:100%; padding:12px; margin-top:12px; background: linear-gradient(135deg,#ff7a7a 0%,#ff4e50 100%); color:white; border:none; border-radius:6px; font-weight:700; cursor:pointer; }
        .small { text-align:center; margin-top:12px; color:#666; }
        a { color:#ff4e50; text-decoration:none; font-weight:700; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Selamat Datang</h2>
        <?php if (isset($_GET['timeout'])): ?>
            <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; margin-bottom: 16px; text-align: center;">
                Session Anda telah berakhir. Silakan login kembali.
            </div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="nama@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Masukkan password" required>
            </div>

            <!-- reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($RECAPTCHA_SITEKEY); ?>"></div>

            <button class="btn" type="submit" name="login">Masuk</button>
        </form>

        <div class="small">
            Belum punya akun? <a href="register.php">Daftar</a>
        </div>
    </div>
</body>
</html>