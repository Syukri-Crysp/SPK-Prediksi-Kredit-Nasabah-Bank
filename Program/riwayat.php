<?php
// riwayat.php
// Riwayat pengujian prediksi kredit

require_once 'includes/header.php';

$db = getDB();
$error = '';
$success = '';

// --- PROSES CRUD ---

// 1. Update Label Aktual (Untuk membandingkan prediksi vs kenyataan)
if (isset($_POST['action']) && $_POST['action'] === 'update_aktual') {
    $id_kredit = (int)$_POST['id_kredit'];
    $label_aktual = $_POST['label_aktual'];

    if (!empty($id_kredit) && !empty($label_aktual)) {
        try {
            $stmt = $db->prepare("UPDATE riwayat SET label_aktual = ? WHERE Id_kredit = ?");
            $stmt->execute([$label_aktual, $id_kredit]);
            $success = "Label aktual untuk ID Kredit #$id_kredit berhasil diset ke '$label_aktual'.";
        } catch (PDOException $e) {
            $error = "Gagal memperbarui label aktual: " . $e->getMessage();
        }
    } else {
        $error = "Harap lengkapi data!";
    }
}

// 2. Hapus Riwayat Spesifik
if (isset($_GET['delete'])) {
    if ($_SESSION['user']['role'] !== 'admin') {
        $error = "Akses ditolak! Hanya Administrator yang dapat menghapus log riwayat.";
    } else {
        $id_del = (int)$_GET['delete'];
        try {
            $stmt = $db->prepare("DELETE FROM riwayat WHERE Id_kredit = ?");
            $stmt->execute([$id_del]);
            $success = "Log riwayat pengujian #$id_del berhasil dihapus.";
        } catch (PDOException $e) {
            $error = "Gagal menghapus log riwayat: " . $e->getMessage();
        }
    }
}

// 3. Bersihkan Seluruh Riwayat
if (isset($_GET['clear_all']) && $_GET['clear_all'] === '1') {
    if ($_SESSION['user']['role'] !== 'admin') {
        $error = "Akses ditolak! Hanya Administrator yang dapat membersihkan riwayat.";
    } else {
        try {
            $db->exec("DELETE FROM riwayat");
            $success = "Seluruh log riwayat prediksi berhasil dibersihkan.";
        } catch (PDOException $e) {
            $error = "Gagal membersihkan riwayat: " . $e->getMessage();
        }
    }
}

// --- PEMBACAAN DATA DENGAN PENCARIAN & PAGINATION ---

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    if ($search !== '') {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM riwayat WHERE Id_nasabah LIKE ? OR hasil_prediksi LIKE ?");
        $countStmt->execute(["%$search%", "%$search%"]);
        $totalData = $countStmt->fetchColumn();

        $query = "SELECT * FROM riwayat WHERE Id_nasabah LIKE ? OR hasil_prediksi LIKE ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $dataStmt = $db->prepare($query);
        $dataStmt->execute(["%$search%", "%$search%"]);
    } else {
        $totalData = $db->query("SELECT COUNT(*) FROM riwayat")->fetchColumn();
        
        $query = "SELECT * FROM riwayat ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $dataStmt = $db->prepare($query);
        $dataStmt->execute();
    }
    
    $riwayatList = $dataStmt->fetchAll();
    $totalPages = ceil($totalData / $limit);
} catch (PDOException $e) {
    die("Gagal membaca riwayat: " . $e->getMessage());
}
?>

<!-- Header Section -->
<div class="page-header">
    <div class="page-title">
        <h2>Riwayat Pengujian Prediksi</h2>
        <p>Log hasil pengujian kelayakan kredit nasabah menggunakan KNN</p>
    </div>
    <?php if ($totalData > 0 && $_SESSION['user']['role'] === 'admin'): ?>
        <a href="riwayat.php?clear_all=1" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus seluruh log riwayat prediksi?')">
            <i data-lucide="trash-2"></i>
            <span>Bersihkan Semua Riwayat</span>
        </a>
    <?php endif; ?>
</div>

<!-- Alert Messages -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <i data-lucide="check-circle" class="alert-icon"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i data-lucide="alert-circle" class="alert-icon"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Search Card -->
<div class="card">
    <form action="riwayat.php" method="GET" style="display: flex; gap: 12px; align-items: center;">
        <div class="input-wrapper" style="flex-grow: 1;">
            <i data-lucide="search" class="input-icon"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari berdasarkan ID Nasabah atau Hasil Prediksi...">
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($search !== ''): ?>
            <a href="riwayat.php" class="btn btn-danger">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Data Table Card -->
<div class="card">
    <div class="card-title-bar">
        <h3>Log Riwayat Pengujian (<?php echo $totalData; ?> baris)</h3>
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
                    <th>Keuangan (Pendapatan/Pinjaman/Tenor)</th>
                    <th>Parameter KNN</th>
                    <th>Hasil Prediksi</th>
                    <th>Label Aktual (Riil)</th>
                    <th>Kesesuaian</th>
                    <th style="width: 120px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayatList)): ?>
                    <tr>
                        <td colspan="12" style="text-align: center; color: var(--text-secondary);">Belum ada riwayat prediksi tersimpan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($riwayatList as $row): 
                        $totalIncome = $row['pendapatan'] + $row['pendapatan_bersama'];
                        $accuracyStatus = '-';
                        if ($row['label_aktual'] !== null) {
                            $accuracyStatus = ($row['hasil_prediksi'] === $row['label_aktual']) ? 'Sesuai' : 'Tidak Sesuai';
                        }
                    ?>
                        <tr>
                            <td style="font-size:0.8rem; color:var(--text-secondary);"><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>#<?php echo $row['Id_kredit']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['Id_nasabah']); ?></strong></td>
                            <td><?php echo ($row['Jenis_Kelamin'] === 'Laki-laki') ? 'L' : 'P'; ?></td>
                            <td><?php echo htmlspecialchars($row['Edukasi']); ?></td>
                            <td><?php echo htmlspecialchars($row['Status_Pernikahan']); ?></td>
                            <td style="font-size: 0.85rem;">
                                Inc: Rp<?php echo number_format($totalIncome, 0, ',', '.'); ?><br>
                                Loan: Rp<?php echo number_format($row['Jumlah_pinjaman'], 0, ',', '.'); ?><br>
                                Tenor: <?php echo $row['jangka_waktu']; ?> bln
                            </td>
                            <td style="font-size: 0.85rem; color: var(--color-secondary);">
                                K = <?php echo $row['k_value']; ?>
                            </td>
                            <td>
                                <?php if ($row['hasil_prediksi'] === 'Lancar'): ?>
                                    <span class="badge badge-success">Lancar</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Macet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['label_aktual'] === null): ?>
                                    <button class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem; border-radius: 6px;"
                                             onclick="openUpdateModal(<?php echo $row['Id_kredit']; ?>, '<?php echo htmlspecialchars($row['Id_nasabah']); ?>')">
                                        Set Aktual
                                    </button>
                                <?php elseif ($row['label_aktual'] === 'Lancar'): ?>
                                    <span class="badge badge-success" style="cursor: pointer;" onclick="openUpdateModal(<?php echo $row['Id_kredit']; ?>, '<?php echo htmlspecialchars($row['Id_nasabah']); ?>')">Lancar</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="cursor: pointer;" onclick="openUpdateModal(<?php echo $row['Id_kredit']; ?>, '<?php echo htmlspecialchars($row['Id_nasabah']); ?>')">Macet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($accuracyStatus === 'Sesuai'): ?>
                                    <span class="badge badge-success">
                                        <i data-lucide="check" style="width:12px; height:12px; margin-right:4px;"></i>Sesuai
                                    </span>
                                <?php elseif ($accuracyStatus === 'Tidak Sesuai'): ?>
                                    <span class="badge badge-danger">
                                        <i data-lucide="x" style="width:12px; height:12px; margin-right:4px;"></i>Salah
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">Menunggu</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                    <a href="cetak_laporan.php?id=<?php echo $row['Id_kredit']; ?>" 
                                       class="btn btn-primary btn-sm" style="padding: 6px 10px;" title="Cetak Laporan PDF" target="_blank">
                                        <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                                    </a>
                                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                        <a href="riwayat.php?delete=<?php echo $row['Id_kredit']; ?>" 
                                           class="btn btn-danger btn-sm" style="padding: 6px 10px;"
                                           onclick="return confirm('Hapus log riwayat ini?')" title="Hapus Log">
                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="riwayat.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="page-link <?php echo ($page === $i) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ================= MODAL UPDATE LABEL AKTUAL ================= -->
<div id="modalUpdate" class="modal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Set Label Aktual (#<span id="title_id_kredit"></span>)</h3>
            <button class="btn-close" onclick="closeUpdateModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="riwayat.php" method="POST">
            <input type="hidden" name="action" value="update_aktual">
            <input type="hidden" id="input_id_kredit" name="id_kredit">
            <div class="modal-body">
                <p style="font-size:0.9rem; color: var(--text-secondary); margin-bottom: 16px;">
                    Tentukan label realita nasabah <strong><span id="title_id_nasabah"></span></strong> untuk membandingkan prediksi algoritma dengan kejadian nyata di lapangan.
                </p>
                <div class="form-group">
                    <label for="label_aktual">Label Kelayakan Kredit Aktual</label>
                    <div class="input-wrapper">
                        <i data-lucide="award" class="input-icon"></i>
                        <select id="label_aktual" name="label_aktual" required>
                            <option value="Lancar">Lancar (Kelayakan Baik)</option>
                            <option value="Macet">Macet (Berisiko Macet)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Label</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUpdateModal(idKredit, idNasabah) {
        document.getElementById('title_id_kredit').innerText = idKredit;
        document.getElementById('input_id_kredit').value = idKredit;
        document.getElementById('title_id_nasabah').innerText = idNasabah;
        document.getElementById('modalUpdate').classList.add('show');
    }

    function closeUpdateModal() {
        document.getElementById('modalUpdate').classList.remove('remove');
        document.getElementById('modalUpdate').classList.remove('show');
    }

    window.onclick = function(event) {
        let mUp = document.getElementById('modalUpdate');
        if (event.target == mUp) closeUpdateModal();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
