<?php
// nasabah.php
// Pengelolaan Data Master Nasabah (CRUD)

require_once 'includes/header.php';

if ($_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini hanya untuk Administrator.'); window.location.href='index.php';</script>";
    exit;
}

$db = getDB();
$error = '';
$success = '';

// --- PROSES CRUD ---

// 1. Tambah Nasabah
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $id_nasabah = strtoupper(trim($_POST['id_nasabah']));
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $edukasi = $_POST['edukasi'];
    $status_pernikahan = $_POST['status_pernikahan'];

    if (!empty($id_nasabah) && !empty($jenis_kelamin) && !empty($edukasi) && !empty($status_pernikahan)) {
        try {
            // Cek duplikasi ID
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM nasabah WHERE Id_Nasabah = ?");
            $checkStmt->execute([$id_nasabah]);
            if ($checkStmt->fetchColumn() > 0) {
                $error = "Gagal menambah data: ID Nasabah '$id_nasabah' sudah terdaftar!";
            } else {
                $stmt = $db->prepare("INSERT INTO nasabah (Id_Nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_nasabah, $jenis_kelamin, $edukasi, $status_pernikahan]);
                $success = "Data Nasabah '$id_nasabah' berhasil ditambahkan.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi semua kolom!";
    }
}

// 2. Edit Nasabah
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_nasabah = trim($_POST['id_nasabah']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $edukasi = $_POST['edukasi'];
    $status_pernikahan = $_POST['status_pernikahan'];

    if (!empty($id_nasabah) && !empty($jenis_kelamin) && !empty($edukasi) && !empty($status_pernikahan)) {
        try {
            // Update tabel nasabah
            $stmt = $db->prepare("UPDATE nasabah SET Jenis_Kelamin = ?, Edukasi = ?, Status_Pernikahan = ? WHERE Id_Nasabah = ?");
            $stmt->execute([$jenis_kelamin, $edukasi, $status_pernikahan, $id_nasabah]);
            
            // Sinkronkan ke dataset (Data Latih) jika data latih menggunakan ID nasabah ini
            $stmtSyncDataset = $db->prepare("UPDATE dataset SET Jenis_Kelamin = ?, Edukasi = ?, Status_Pernikahan = ? WHERE Id_nasabah = ?");
            $stmtSyncDataset->execute([$jenis_kelamin, $edukasi, $status_pernikahan, $id_nasabah]);

            // Sinkronkan ke riwayat jika ada
            $stmtSyncRiwayat = $db->prepare("UPDATE riwayat SET Jenis_Kelamin = ?, Edukasi = ?, Status_Pernikahan = ? WHERE Id_nasabah = ?");
            $stmtSyncRiwayat->execute([$jenis_kelamin, $edukasi, $status_pernikahan, $id_nasabah]);

            $success = "Data Nasabah '$id_nasabah' berhasil diperbarui.";
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi semua kolom!";
    }
}

// 3. Hapus Nasabah
if (isset($_GET['delete'])) {
    $id_del = $_GET['delete'];
    try {
        // Hapus nasabah (akan otomatis cascade delete pada dataset & riwayat sesuai schema SQLite FK)
        $stmt = $db->prepare("DELETE FROM nasabah WHERE Id_Nasabah = ?");
        $stmt->execute([$id_del]);
        $success = "Data Nasabah '$id_del' berhasil dihapus dari sistem.";
    } catch (PDOException $e) {
        $error = "Gagal menghapus data: " . $e->getMessage();
    }
}

// --- PEMBACAAN DATA DENGAN PENCARIAN & PAGINATION ---

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

try {
    if ($search !== '') {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM nasabah WHERE Id_Nasabah LIKE ?");
        $countStmt->execute(["%$search%"]);
        $totalData = $countStmt->fetchColumn();

        $query = "SELECT * FROM nasabah WHERE Id_Nasabah LIKE ? ORDER BY Id_Nasabah ASC LIMIT $limit OFFSET $offset";
        $dataStmt = $db->prepare($query);
        $dataStmt->execute(["%$search%"]);
    } else {
        $totalData = $db->query("SELECT COUNT(*) FROM nasabah")->fetchColumn();
        
        $query = "SELECT * FROM nasabah ORDER BY Id_Nasabah ASC LIMIT $limit OFFSET $offset";
        $dataStmt = $db->prepare($query);
        $dataStmt->execute();
    }
    
    $nasabahList = $dataStmt->fetchAll();
    $totalPages = ceil($totalData / $limit);
} catch (PDOException $e) {
    die("Gagal membaca data: " . $e->getMessage());
}
?>

<!-- Header Section -->
<div class="page-header">
    <div class="page-title">
        <h2>Kelola Master Nasabah</h2>
        <p>Manajemen data pokok profil identitas nasabah pemohon kredit</p>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i data-lucide="plus"></i>
        <span>Tambah Nasabah</span>
    </button>
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
    <form action="nasabah.php" method="GET" style="display: flex; gap: 12px; align-items: center;">
        <div class="input-wrapper" style="flex-grow: 1;">
            <i data-lucide="search" class="input-icon"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari berdasarkan ID Nasabah (misal: NSB001)...">
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($search !== ''): ?>
            <a href="nasabah.php" class="btn btn-danger">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Data Table Card -->
<div class="card">
    <div class="card-title-bar">
        <h3>Daftar Nasabah Terdaftar (<?php echo $totalData; ?> baris)</h3>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Nasabah</th>
                    <th>Jenis Kelamin</th>
                    <th>Pendidikan (Edukasi)</th>
                    <th>Status Pernikahan</th>
                    <th style="width: 150px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($nasabahList)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary);">Tidak ada data nasabah ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = $offset + 1;
                    foreach ($nasabahList as $row): 
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['Id_Nasabah']); ?></strong></td>
                            <td>
                                <?php if ($row['Jenis_Kelamin'] === 'Laki-laki'): ?>
                                    <span class="badge badge-info"><i data-lucide="user" style="width:12px;height:12px;margin-right:4px;"></i>Laki-laki</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><i data-lucide="user-plus" style="width:12px;height:12px;margin-right:4px;"></i>Perempuan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['Edukasi'] === 'Lulus'): ?>
                                    <span class="badge badge-success">Lulus</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Tidak Lulus</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['Status_Pernikahan'] === 'Ya'): ?>
                                    <span class="badge badge-success">Ya</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Tidak</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <button class="btn btn-secondary btn-sm" style="padding: 6px 10px;" 
                                            onclick="openEditModal('<?php echo htmlspecialchars($row['Id_Nasabah']); ?>', '<?php echo htmlspecialchars($row['Jenis_Kelamin']); ?>', '<?php echo htmlspecialchars($row['Edukasi']); ?>', '<?php echo htmlspecialchars($row['Status_Pernikahan']); ?>')">
                                        <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                    <a href="nasabah.php?delete=<?php echo urlencode($row['Id_Nasabah']); ?>" 
                                       class="btn btn-danger btn-sm" style="padding: 6px 10px;"
                                       onclick="return confirm('Menghapus nasabah ini juga akan menghapus data latih dan riwayat prediksi yang terkait. Lanjutkan?')">
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                    </a>
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
                <a href="nasabah.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="page-link <?php echo ($page === $i) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ================= MODAL TAMBAH ================= -->
<div id="modalAdd" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Tambah Master Data Nasabah</h3>
            <button class="btn-close" onclick="closeAddModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="nasabah.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_id_nasabah">ID Nasabah</label>
                    <div class="input-wrapper">
                        <i data-lucide="credit-card" class="input-icon"></i>
                        <input type="text" id="add_id_nasabah" name="id_nasabah" placeholder="Contoh: NSB021" required pattern="[A-Za-z0-9]+" title="Hanya huruf dan angka">
                    </div>
                    <small style="color: var(--text-muted); margin-top: 4px; display: block;">Gunakan format alfanumerik yang unik.</small>
                </div>

                <div class="form-group">
                    <label for="add_jenis_kelamin">Jenis Kelamin</label>
                    <div class="input-wrapper">
                        <i data-lucide="users" class="input-icon"></i>
                        <select id="add_jenis_kelamin" name="jenis_kelamin" required>
                            <option value="" disabled selected>Pilih Jenis Kelamin</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_edukasi">Pendidikan (Edukasi)</label>
                    <div class="input-wrapper">
                        <i data-lucide="graduation-cap" class="input-icon"></i>
                        <select id="add_edukasi" name="edukasi" required>
                            <option value="" disabled selected>Pilih Status Pendidikan</option>
                            <option value="Lulus">Lulus</option>
                            <option value="Tidak Lulus">Tidak Lulus</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_status_pernikahan">Status Pernikahan</label>
                    <div class="input-wrapper">
                        <i data-lucide="heart" class="input-icon"></i>
                        <select id="add_status_pernikahan" name="status_pernikahan" required>
                            <option value="" disabled selected>Pilih Status Pernikahan</option>
                            <option value="Ya">Ya</option>
                            <option value="Tidak">Tidak</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL EDIT ================= -->
<div id="modalEdit" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Data Nasabah</h3>
            <button class="btn-close" onclick="closeEditModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="nasabah.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_id_nasabah">ID Nasabah</label>
                    <div class="input-wrapper">
                        <i data-lucide="credit-card" class="input-icon"></i>
                        <input type="text" id="edit_id_nasabah" name="id_nasabah" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted); cursor: not-allowed;">
                    </div>
                    <small style="color: var(--text-muted); margin-top: 4px; display: block;">ID Nasabah tidak dapat diubah karena merupakan kunci relasi.</small>
                </div>

                <div class="form-group">
                    <label for="edit_jenis_kelamin">Jenis Kelamin</label>
                    <div class="input-wrapper">
                        <i data-lucide="users" class="input-icon"></i>
                        <select id="edit_jenis_kelamin" name="jenis_kelamin" required>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_edukasi">Pendidikan (Edukasi)</label>
                    <div class="input-wrapper">
                        <i data-lucide="graduation-cap" class="input-icon"></i>
                        <select id="edit_edukasi" name="edukasi" required>
                            <option value="Lulus">Lulus</option>
                            <option value="Tidak Lulus">Tidak Lulus</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_status_pernikahan">Status Pernikahan</label>
                    <div class="input-wrapper">
                        <i data-lucide="heart" class="input-icon"></i>
                        <select id="edit_status_pernikahan" name="status_pernikahan" required>
                            <option value="Ya">Ya</option>
                            <option value="Tidak">Tidak</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal Tambah
    function openAddModal() {
        document.getElementById('modalAdd').classList.add('show');
    }
    function closeAddModal() {
        document.getElementById('modalAdd').classList.remove('show');
    }

    // Modal Edit
    function openEditModal(id, jk, edu, nikah) {
        document.getElementById('edit_id_nasabah').value = id;
        document.getElementById('edit_jenis_kelamin').value = jk;
        document.getElementById('edit_edukasi').value = edu;
        document.getElementById('edit_status_pernikahan').value = nikah;
        document.getElementById('modalEdit').classList.add('show');
    }
    function closeEditModal() {
        document.getElementById('modalEdit').classList.remove('show');
    }

    // Menutup modal dengan mengklik area di luar konten
    window.onclick = function(event) {
        let mAdd = document.getElementById('modalAdd');
        let mEdit = document.getElementById('modalEdit');
        if (event.target == mAdd) closeAddModal();
        if (event.target == mEdit) closeEditModal();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
