<?php
// evaluasi.php
// Halaman evaluasi akurasi model KNN (Confusion Matrix)

require_once 'includes/header.php';

if ($_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini hanya untuk Administrator.'); window.location.href='index.php';</script>";
    exit;
}
require_once 'knn.php';

$db = getDB();
$evaluationResult = null;
$error = '';

$k_value = isset($_GET['k_value']) ? (int)$_GET['k_value'] : 3;
$test_ratio = isset($_GET['test_ratio']) ? (float)$_GET['test_ratio'] : 0.3;

try {
    $knn = new KNN($db);
    $evaluationResult = $knn->evaluate($test_ratio, $k_value);
    
    if (isset($evaluationResult['error'])) {
        $error = $evaluationResult['error'];
        $evaluationResult = null;
    }
} catch (Exception $e) {
    $error = "Terjadi kesalahan dalam evaluasi: " . $e->getMessage();
}
?>

<!-- Header Section -->
<div class="page-header">
    <div class="page-title">
        <h2>Evaluasi Performa Model</h2>
        <p>Analisis akurasi algoritma KNN menggunakan metode pengujian Train-Test Split & Confusion Matrix</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i data-lucide="alert-circle" class="alert-icon"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Control Card -->
<div class="card">
    <div class="card-title-bar">
        <h3>Parameter Evaluasi</h3>
    </div>
    <form action="evaluasi.php" method="GET" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom:0; min-width: 180px;">
            <label for="k_value">K (Tetangga Terdekat)</label>
            <div class="input-wrapper">
                <i data-lucide="hash" class="input-icon"></i>
                <select id="k_value" name="k_value" required>
                    <option value="3" <?php echo ($k_value === 3) ? 'selected' : ''; ?>>K = 3</option>
                    <option value="5" <?php echo ($k_value === 5) ? 'selected' : ''; ?>>K = 5</option>
                    <option value="7" <?php echo ($k_value === 7) ? 'selected' : ''; ?>>K = 7</option>
                    <option value="9" <?php echo ($k_value === 9) ? 'selected' : ''; ?>>K = 9</option>
                    <option value="11" <?php echo ($k_value === 11) ? 'selected' : ''; ?>>K = 11</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-bottom:0; min-width: 180px;">
            <label for="test_ratio">Rasio Data Uji (Test Set)</label>
            <div class="input-wrapper">
                <i data-lucide="percent" class="input-icon"></i>
                <select id="test_ratio" name="test_ratio" required>
                    <option value="0.2" <?php echo ($test_ratio == 0.2) ? 'selected' : ''; ?>>20% Data Uji (80% Latih)</option>
                    <option value="0.3" <?php echo ($test_ratio == 0.3) ? 'selected' : ''; ?>>30% Data Uji (70% Latih)</option>
                    <option value="0.4" <?php echo ($test_ratio == 0.4) ? 'selected' : ''; ?>>40% Data Uji (60% Latih)</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i data-lucide="refresh-cw"></i>
            <span>Jalankan Evaluasi</span>
        </button>
    </form>
</div>

<?php if ($evaluationResult !== null): ?>
    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <div class="card metric-card" style="border-left: 4px solid var(--color-primary);">
            <div class="metric-info">
                <h3>Akurasi Model</h3>
                <div class="metric-value"><?php echo round($evaluationResult['accuracy'], 2); ?>%</div>
            </div>
            <div class="metric-icon-wrapper bg-indigo-light">
                <i data-lucide="target" style="width:24px; height:24px;"></i>
            </div>
        </div>

        <div class="card metric-card" style="border-left: 4px solid var(--color-teal);">
            <div class="metric-info">
                <h3>Presisi (Lancar)</h3>
                <div class="metric-value"><?php echo round($evaluationResult['lancar']['precision'], 2); ?>%</div>
            </div>
            <div class="metric-icon-wrapper bg-teal-light">
                <i data-lucide="check-check" style="width:24px; height:24px;"></i>
            </div>
        </div>

        <div class="card metric-card" style="border-left: 4px solid var(--color-success);">
            <div class="metric-info">
                <h3>Recall (Lancar)</h3>
                <div class="metric-value"><?php echo round($evaluationResult['lancar']['recall'], 2); ?>%</div>
            </div>
            <div class="metric-icon-wrapper bg-emerald-light">
                <i data-lucide="zoom-in" style="width:24px; height:24px;"></i>
            </div>
        </div>

        <div class="card metric-card" style="border-left: 4px solid var(--color-secondary);">
            <div class="metric-info">
                <h3>F1-Score (Lancar)</h3>
                <div class="metric-value"><?php echo round($evaluationResult['lancar']['f1_score'], 2); ?>%</div>
            </div>
            <div class="metric-icon-wrapper bg-rose-light">
                <i data-lucide="activity" style="width:24px; height:24px;"></i>
            </div>
        </div>
    </div>

    <!-- Analysis Grid -->
    <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 32px;">
        <!-- Confusion Matrix Card -->
        <div class="card" style="display:flex; flex-direction:column;">
            <div class="card-title-bar">
                <h3>Matriks Kebingungan (Confusion Matrix)</h3>
            </div>
            <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom: 20px;">
                Confusion Matrix memetakan perbandingan antara label kebenaran aktual (kenyataan) terhadap hasil prediksi model KNN:
            </p>

            <div class="matrix-container" style="flex-grow:1; display:flex; align-items:center; justify-content:center;">
                <div class="matrix-grid">
                    <!-- Row 1: Header Columns -->
                    <div></div>
                    <div class="matrix-header-cell">Prediksi Lancar</div>
                    <div class="matrix-header-cell">Prediksi Macet</div>

                    <!-- Row 2: Actual Lancar -->
                    <div class="matrix-label-cell">Aktual Lancar</div>
                    
                    <!-- True Lancar (TL) -->
                    <div class="matrix-data-cell bg-cell-success">
                        <span class="matrix-cell-value"><?php echo $evaluationResult['confusion_matrix']['Lancar_Lancar']; ?></span>
                        <span class="matrix-cell-label">True Lancar (TL)</span>
                    </div>
                    
                    <!-- False Macet (FM) -->
                    <div class="matrix-data-cell bg-cell-danger">
                        <span class="matrix-cell-value"><?php echo $evaluationResult['confusion_matrix']['Lancar_Macet']; ?></span>
                        <span class="matrix-cell-label">False Macet</span>
                    </div>

                    <!-- Row 3: Actual Macet -->
                    <div class="matrix-label-cell">Aktual Macet</div>
                    
                    <!-- False Lancar (FL) -->
                    <div class="matrix-data-cell bg-cell-danger">
                        <span class="matrix-cell-value"><?php echo $evaluationResult['confusion_matrix']['Macet_Lancar']; ?></span>
                        <span class="matrix-cell-label">False Lancar</span>
                    </div>
                    
                    <!-- True Macet (TM) -->
                    <div class="matrix-data-cell bg-cell-success">
                        <span class="matrix-cell-value"><?php echo $evaluationResult['confusion_matrix']['Macet_Macet']; ?></span>
                        <span class="matrix-cell-label">True Macet (TM)</span>
                    </div>
                </div>
            </div>

            <div class="formula-card" style="padding: 12px 16px; font-size:0.8rem; color: var(--text-secondary); margin-top:20px; line-height:1.6;">
                <strong>Keterangan Teori:</strong><br>
                • <strong>True Lancar (TL)</strong>: Nasabah lancar diprediksi layak/lancar (Benar).<br>
                • <strong>True Macet (TM)</strong>: Nasabah macet diprediksi macet (Benar).<br>
                • <strong>False Lancar</strong>: Nasabah macet diprediksi layak/lancar (Salah/Beresiko Tinggi!).<br>
                • <strong>False Macet</strong>: Nasabah lancar diprediksi macet (Salah).
            </div>
        </div>

        <!-- Partition Info & Performance Table -->
        <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div class="card-title-bar">
                    <h3>Statistik Partisi Data</h3>
                </div>
                <div style="display:flex; justify-content:space-around; margin-bottom: 24px;">
                    <div style="text-align:center;">
                        <span style="font-size:0.85rem; color:var(--text-secondary);">Total Dataset</span>
                        <h4 style="font-size:1.6rem; color:#fff; margin-top:4px;"><?php echo $evaluationResult['total_data']; ?></h4>
                    </div>
                    <div style="text-align:center; border-left: 1px solid var(--border-light); padding-left: 32px;">
                        <span style="font-size:0.85rem; color:var(--text-secondary);">Subset Latih (Training)</span>
                        <h4 style="font-size:1.6rem; color:var(--color-primary); margin-top:4px;"><?php echo $evaluationResult['total_latih']; ?></h4>
                    </div>
                    <div style="text-align:center; border-left: 1px solid var(--border-light); padding-left: 32px;">
                        <span style="font-size:0.85rem; color:var(--text-secondary);">Subset Uji (Testing)</span>
                        <h4 style="font-size:1.6rem; color:var(--color-secondary); margin-top:4px;"><?php echo $evaluationResult['total_uji']; ?></h4>
                    </div>
                </div>

                <div class="card-title-bar">
                    <h3>Detail Perbandingan Prediksi Uji</h3>
                </div>
                <div class="table-responsive" style="max-height: 240px; overflow-y:auto;">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>ID Nasabah</th>
                                <th>Aktual</th>
                                <th>Prediksi</th>
                                <th>Hasil</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluationResult['detail_uji'] as $rowUji): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($rowUji['Id_nasabah']); ?></strong></td>
                                    <td><?php echo $rowUji['aktual']; ?></td>
                                    <td><?php echo $rowUji['prediksi']; ?></td>
                                    <td>
                                        <?php if ($rowUji['sukses']): ?>
                                            <span class="badge badge-success"><i data-lucide="check" style="width:12px;height:12px;margin-right:2px;display:inline-block;vertical-align:middle;"></i>Benar</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i data-lucide="x" style="width:12px;height:12px;margin-right:2px;display:inline-block;vertical-align:middle;"></i>Salah</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
