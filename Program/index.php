<?php
// index.php
// Dashboard Utama Aplikasi

require_once 'includes/header.php';
require_once 'knn.php';

$db = getDB();

try {
    // 1. Metrik Kecepatan
    $totalNasabah = $db->query("SELECT COUNT(*) FROM nasabah")->fetchColumn();
    $totalDataset = $db->query("SELECT COUNT(*) FROM dataset")->fetchColumn();
    $totalRiwayat = $db->query("SELECT COUNT(*) FROM riwayat")->fetchColumn();

    // Akurasi Default Model (Rasio 30%, K = 3)
    $knn = new KNN($db);
    $eval = $knn->evaluate(0.3, 3);
    $accuracy = (!isset($eval['error'])) ? round($eval['accuracy'], 1) : 0;

    // 2. Data Grafik 1: Distribusi Dataset Latih
    $dsLancar = $db->query("SELECT COUNT(*) FROM dataset WHERE label_aktual = 'Lancar'")->fetchColumn();
    $dsMacet = $db->query("SELECT COUNT(*) FROM dataset WHERE label_aktual = 'Macet'")->fetchColumn();

    // 3. Data Grafik 2: Hasil Prediksi Riwayat Uji
    $rwLancar = $db->query("SELECT COUNT(*) FROM riwayat WHERE hasil_prediksi = 'Lancar'")->fetchColumn();
    $rwMacet = $db->query("SELECT COUNT(*) FROM riwayat WHERE hasil_prediksi = 'Macet'")->fetchColumn();

    // 4. Riwayat Terbaru (Last 5)
    $stmtRecent = $db->query("SELECT * FROM riwayat ORDER BY created_at DESC LIMIT 5");
    $recentList = $stmtRecent->fetchAll();

} catch (PDOException $e) {
    die("Gagal memuat dashboard: " . $e->getMessage());
}
?>

<!-- Welcome Banner -->
<div class="card" style="background: var(--bg-welcome); border-color: rgba(99, 102, 241, 0.3); padding: 32px; border-radius: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h2 style="font-size: 1.8rem; font-weight: 700; color: var(--text-welcome-title); margin-bottom: 8px;">
                Halo, <?php echo htmlspecialchars($_SESSION['user']['nama']); ?>! 👋
            </h2>
            <p style="color: var(--text-welcome-desc); max-width: 600px; font-size: 0.98rem; line-height: 1.6;">
                Selamat datang di **Sistem Pendukung Keputusan Analisis Kelayakan Kredit**. Sistem ini siap membantu menguji kelayakan calon debitur (Lancar / Macet) secara ilmiah berbasis algoritma **K-Nearest Neighbor (KNN)**.
            </p>
        </div>
        <a href="prediksi.php" class="btn btn-primary" style="padding: 14px 28px;">
            <i data-lucide="play-circle"></i>
            <span>Mulai Uji Prediksi</span>
        </a>
    </div>
</div>

<!-- Metrics Stats Grid -->
<div class="metrics-grid">
    <div class="card metric-card" style="border-left: 4px solid var(--color-primary);">
        <div class="metric-info">
            <h3>Master Nasabah</h3>
            <div class="metric-value"><?php echo $totalNasabah; ?></div>
        </div>
        <div class="metric-icon-wrapper bg-indigo-light">
            <i data-lucide="users" style="width: 24px; height: 24px;"></i>
        </div>
    </div>

    <div class="card metric-card" style="border-left: 4px solid var(--color-teal);">
        <div class="metric-info">
            <h3>Dataset Latih (Histori)</h3>
            <div class="metric-value"><?php echo $totalDataset; ?></div>
        </div>
        <div class="metric-icon-wrapper bg-teal-light">
            <i data-lucide="database" style="width: 24px; height: 24px;"></i>
        </div>
    </div>

    <div class="card metric-card" style="border-left: 4px solid var(--color-success);">
        <div class="metric-info">
            <h3>Log Pengujian</h3>
            <div class="metric-value"><?php echo $totalRiwayat; ?></div>
        </div>
        <div class="metric-icon-wrapper bg-emerald-light">
            <i data-lucide="history" style="width: 24px; height: 24px;"></i>
        </div>
    </div>

    <div class="card metric-card" style="border-left: 4px solid var(--color-secondary);">
        <div class="metric-info">
            <h3>Akurasi Model (K=3)</h3>
            <div class="metric-value">
                <?php echo ($accuracy > 0) ? $accuracy . '%' : 'N/A'; ?>
            </div>
        </div>
        <div class="metric-icon-wrapper bg-rose-light">
            <i data-lucide="target" style="width: 24px; height: 24px;"></i>
        </div>
    </div>
</div>

<!-- Graphical Dashboard Section -->
<div class="dashboard-grid">
    <!-- Chart 1: Dataset Latih Distribution -->
    <div class="card">
        <div class="card-title-bar">
            <h3>Proporsi Kelayakan Dataset Latih</h3>
            <i data-lucide="pie-chart" style="color:var(--text-muted); width: 18px; height:18px;"></i>
        </div>
        <div class="chart-container">
            <canvas id="datasetChart"></canvas>
        </div>
    </div>

    <!-- Chart 2: Prediction Results Distribution -->
    <div class="card">
        <div class="card-title-bar">
            <h3>Proporsi Hasil Uji Prediksi</h3>
            <i data-lucide="donut-chart" style="color:var(--text-muted); width: 18px; height:18px;"></i>
        </div>
        <div class="chart-container">
            <canvas id="riwayatChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Predictions Log -->
<div class="card">
    <div class="card-title-bar">
        <h3>5 Log Pengujian Prediksi Terbaru</h3>
        <a href="riwayat.php" class="btn btn-secondary" style="padding: 6px 12px; font-size:0.8rem;">Lihat Semua</a>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Waktu Uji</th>
                    <th>ID Kredit</th>
                    <th>ID Nasabah</th>
                    <th>JK</th>
                    <th>Edukasi</th>
                    <th>Status Pernikahan</th>
                    <th>Tanggungan</th>
                    <th>Parameter KNN</th>
                    <th>Prediksi KNN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentList)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-secondary);">Belum ada log pengujian kredit terbaru.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentList as $row): ?>
                        <tr>
                            <td style="font-size:0.8rem; color:var(--text-secondary);"><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>#<?php echo $row['Id_kredit']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['Id_nasabah']); ?></strong></td>
                            <td><?php echo ($row['Jenis_Kelamin'] === 'Laki-laki') ? 'Laki-laki' : 'Perempuan'; ?></td>
                            <td><?php echo htmlspecialchars($row['Edukasi']); ?></td>
                            <td><?php echo htmlspecialchars($row['Status_Pernikahan']); ?></td>
                            <td><?php echo $row['Tangguan']; ?> orang</td>
                            <td style="color: var(--color-secondary);">K = <?php echo $row['k_value']; ?></td>
                            <td>
                                <?php if ($row['hasil_prediksi'] === 'Lancar'): ?>
                                    <span class="badge badge-success">Lancar</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Macet</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart JS Initialization Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Data dari PHP
        const dsLancar = <?php echo (int)$dsLancar; ?>;
        const dsMacet = <?php echo (int)$dsMacet; ?>;
        const rwLancar = <?php echo (int)$rwLancar; ?>;
        const rwMacet = <?php echo (int)$rwMacet; ?>;

        // Tentukan warna teks chart berdasarkan tema saat ini
        const activeTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const chartTextColor = activeTheme === 'light' ? '#475569' : '#e5e7eb';
        
        // Atur default warna teks Chart.js
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = chartTextColor;
        }

        // Chart 1: Dataset Latih Chart
        const ctxDataset = document.getElementById('datasetChart').getContext('2d');
        window.datasetChart = new Chart(ctxDataset, {
            type: 'doughnut',
            data: {
                labels: ['Lancar', 'Macet'],
                datasets: [{
                    data: [dsLancar, dsMacet],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.6)', // Emerald green
                        'rgba(239, 68, 68, 0.6)'   // Rose red
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: chartTextColor, font: { family: 'Outfit', size: 12 } }
                    }
                }
            }
        });

        // Chart 2: Riwayat Uji Chart
        const ctxRiwayat = document.getElementById('riwayatChart').getContext('2d');
        window.riwayatChart = new Chart(ctxRiwayat, {
            type: 'pie',
            data: {
                labels: ['Lancar', 'Macet'],
                datasets: [{
                    data: [rwLancar, rwMacet],
                    backgroundColor: [
                        'rgba(0, 94, 106, 0.6)', // BNI Teal
                        'rgba(241, 90, 34, 0.6)'  // BNI Orange
                    ],
                    borderColor: [
                        'rgba(0, 94, 106, 1)',
                        'rgba(241, 90, 34, 1)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: chartTextColor, font: { family: 'Outfit', size: 12 } }
                    }
                }
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
