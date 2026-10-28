<?php
// login.php
// Halaman login utama

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect ke dashboard jika sudah login
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nama' => $user['nama'],
                    'role' => $user['role']
                ];
                header("Location: index.php");
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    } else {
        $error = 'Harap isi semua kolom!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Blocking Theme Selector Script -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <title>Login - Prediksi Kredit Macet Bank BNI Tarandam Padang</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="login-body">
    <!-- Floating Theme Toggle -->
    <div style="position: absolute; top: 20px; right: 20px; z-index: 100;">
        <button id="theme-toggle" class="btn-theme-toggle" aria-label="Ganti Tema">
            <i data-lucide="sun" class="theme-icon-light" style="width: 18px; height: 18px;"></i>
            <i data-lucide="moon" class="theme-icon-dark" style="width: 18px; height: 18px;"></i>
        </button>
    </div>

    <!-- Background Glow Blobs -->
    <div class="glow-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/logo-bni.jpg" alt="BNI Logo" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; margin-bottom: 16px; border: 3px solid var(--color-primary); box-shadow: 0 0 15px rgba(241, 90, 34, 0.4);">
                <h2>Prediksi Kredit Macet Bank BNI Tarandam Padang</h2>
                <p>Silakan masuk untuk mengelola data dan analisis</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i data-lucide="alert-circle" class="alert-icon"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <span>Masuk</span>
                    <i data-lucide="arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Custom JS Utama -->
    <script src="assets/js/main.js?v=1.2"></script>
    <script>
        // Inisialisasi ikon Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
