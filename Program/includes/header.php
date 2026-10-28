<?php
// includes/header.php
// Header layout komponen & Sidebar navigasi

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

$activePage = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['user']['nama'] ?? 'Admin';
$userRole = $_SESSION['user']['role'] ?? 'petugas';
$userInitial = strtoupper(substr($userName, 0, 1));
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
    <title>Sistem Prediksi Kelayakan Kredit KNN</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header" style="gap: 12px; padding: 24px 20px;">
                <img src="assets/logo-bni.jpg" alt="BNI Logo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-primary); box-shadow: 0 0 10px rgba(241, 90, 34, 0.3);">
                <div style="display: flex; flex-direction: column;">
                    <h1 style="font-size: 0.95rem; font-weight: 700; color: #fff; line-height: 1.2;">Prediksi Kredit Macet</h1>
                    <span style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Bank BNI</span>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?php echo ($activePage === 'index.php') ? 'active' : ''; ?>">
                    <a href="index.php">
                        <i data-lucide="layout-dashboard" class="sidebar-menu-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if ($userRole === 'admin'): ?>
                <li class="sidebar-menu-item <?php echo ($activePage === 'nasabah.php') ? 'active' : ''; ?>">
                    <a href="nasabah.php">
                        <i data-lucide="users" class="sidebar-menu-icon"></i>
                        <span>Kelola Nasabah</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?php echo ($activePage === 'dataset.php') ? 'active' : ''; ?>">
                    <a href="dataset.php">
                        <i data-lucide="database" class="sidebar-menu-icon"></i>
                        <span>Dataset Latih</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="sidebar-menu-item <?php echo ($activePage === 'prediksi.php') ? 'active' : ''; ?>">
                    <a href="prediksi.php">
                        <i data-lucide="binary" class="sidebar-menu-icon"></i>
                        <span>Prediksi Kredit</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?php echo ($activePage === 'riwayat.php') ? 'active' : ''; ?>">
                    <a href="riwayat.php">
                        <i data-lucide="history" class="sidebar-menu-icon"></i>
                        <span>Riwayat Uji</span>
                    </a>
                </li>
                <?php if ($userRole === 'admin'): ?>
                <li class="sidebar-menu-item <?php echo ($activePage === 'evaluasi.php') ? 'active' : ''; ?>">
                    <a href="evaluasi.php">
                        <i data-lucide="line-chart" class="sidebar-menu-icon"></i>
                        <span>Evaluasi Model</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?php echo ($activePage === 'pengguna.php') ? 'active' : ''; ?>">
                    <a href="pengguna.php">
                        <i data-lucide="shield" class="sidebar-menu-icon"></i>
                        <span>Kelola Pengguna</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="sidebar-footer">
                <div class="theme-toggle-container">
                    <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">Mode Tampilan</span>
                    <button id="theme-toggle" class="btn-theme-toggle" aria-label="Ganti Tema">
                        <i data-lucide="sun" class="theme-icon-light" style="width: 18px; height: 18px;"></i>
                        <i data-lucide="moon" class="theme-icon-dark" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $userInitial; ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="user-role"><?php echo ($userRole === 'admin') ? 'Administrator' : 'Petugas'; ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-danger btn-block btn-sm" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
                    <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
