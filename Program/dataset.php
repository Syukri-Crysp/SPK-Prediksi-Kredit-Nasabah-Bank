<?php
// dataset.php
// Pengelolaan Data Latih (Dataset)

require_once 'includes/header.php';

if ($_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini hanya untuk Administrator.'); window.location.href='index.php';</script>";
    exit;
}

$db = getDB();
$error = '';
$success = '';

// --- PROSES CRUD, RESET, & IMPORT ---

// 1. Tambah Data Latih
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $id_nasabah = $_POST['id_nasabah'];
    $tangguan = (int)$_POST['tangguan'];
    $pendapatan = (float)$_POST['pendapatan'];
    $pendapatan_bersama = (float)$_POST['pendapatan_bersama'];
    $jumlah_pinjaman = (float)$_POST['jumlah_pinjaman'];
    $jangka_waktu = (int)$_POST['jangka_waktu'];
    $riwayat_kredit = (int)$_POST['riwayat_kredit'];
    $status_pekerjaan = $_POST['status_pekerjaan'];
    $asset = $_POST['asset'];
    $label_aktual = $_POST['label_aktual'];

    if (!empty($id_nasabah) && isset($_POST['tangguan'])) {
        try {
            // Ambil Jenis_Kelamin, Edukasi, dan Status_Pernikahan dari tabel nasabah
            $nsbStmt = $db->prepare("SELECT Jenis_Kelamin, Edukasi, Status_Pernikahan FROM nasabah WHERE Id_Nasabah = ?");
            $nsbStmt->execute([$id_nasabah]);
            $nsb = $nsbStmt->fetch();

            if ($nsb) {
                $jenis_kelamin = $nsb['Jenis_Kelamin'];
                $edukasi = $nsb['Edukasi'];
                $status_pernikahan = $nsb['Status_Pernikahan'];

                $stmt = $db->prepare("INSERT INTO dataset (
                    Id_nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan, Tangguan, pendapatan, 
                    pendapatan_bersama, Jumlah_pinjaman, jangka_waktu, 
                    riwayat_kredit, status_pekerjaan, Asset, label_aktual
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $id_nasabah, $jenis_kelamin, $edukasi, $status_pernikahan, $tangguan, $pendapatan,
                    $pendapatan_bersama, $jumlah_pinjaman, $jangka_waktu,
                    $riwayat_kredit, $status_pekerjaan, $asset, $label_aktual
                ]);
                $success = "Data latih baru untuk nasabah '$id_nasabah' berhasil ditambahkan.";
            } else {
                $error = "ID Nasabah tidak valid!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi semua kolom!";
    }
}

// 2. Edit Data Latih
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_kredit = (int)$_POST['id_kredit'];
    $id_nasabah = $_POST['id_nasabah'];
    $tangguan = (int)$_POST['tangguan'];
    $pendapatan = (float)$_POST['pendapatan'];
    $pendapatan_bersama = (float)$_POST['pendapatan_bersama'];
    $jumlah_pinjaman = (float)$_POST['jumlah_pinjaman'];
    $jangka_waktu = (int)$_POST['jangka_waktu'];
    $riwayat_kredit = (int)$_POST['riwayat_kredit'];
    $status_pekerjaan = $_POST['status_pekerjaan'];
    $asset = $_POST['asset'];
    $label_aktual = $_POST['label_aktual'];

    if (!empty($id_kredit) && !empty($id_nasabah)) {
        try {
            // Ambil Jenis Kelamin, Edukasi, dan Status Pernikahan ter-update dari tabel nasabah
            $nsbStmt = $db->prepare("SELECT Jenis_Kelamin, Edukasi, Status_Pernikahan FROM nasabah WHERE Id_Nasabah = ?");
            $nsbStmt->execute([$id_nasabah]);
            $nsb = $nsbStmt->fetch();

            if ($nsb) {
                $jenis_kelamin = $nsb['Jenis_Kelamin'];
                $edukasi = $nsb['Edukasi'];
                $status_pernikahan = $nsb['Status_Pernikahan'];

                $stmt = $db->prepare("UPDATE dataset SET 
                    Id_nasabah = ?, Jenis_Kelamin = ?, Edukasi = ?, Status_Pernikahan = ?, Tangguan = ?, 
                    pendapatan = ?, pendapatan_bersama = ?, Jumlah_pinjaman = ?, 
                    jangka_waktu = ?, riwayat_kredit = ?, status_pekerjaan = ?, 
                    Asset = ?, label_aktual = ? WHERE Id_kredit = ?");
                
                $stmt->execute([
                    $id_nasabah, $jenis_kelamin, $edukasi, $status_pernikahan, $tangguan, $pendapatan,
                    $pendapatan_bersama, $jumlah_pinjaman, $jangka_waktu,
                    $riwayat_kredit, $status_pekerjaan, $asset, $label_aktual, $id_kredit
                ]);
                $success = "Data latih ID Kredit '$id_kredit' berhasil diperbarui.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap lengkapi semua data!";
    }
}

// 3. Hapus Data Latih
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM dataset WHERE Id_kredit = ?");
        $stmt->execute([$id_del]);
        $success = "Data latih ID Kredit '$id_del' berhasil dihapus.";
    } catch (PDOException $e) {
        $error = "Gagal menghapus: " . $e->getMessage();
    }
}

// 4. Reset Data ke Default
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    try {
        // Kosongkan database dan seed ulang data
        resetDatabase($db);
        $success = "Dataset berhasil di-reset ke data bawaan sistem (20 records).";
    } catch (PDOException $e) {
        $error = "Gagal mereset database: " . $e->getMessage();
    }
}

// 5. Import CSV
if (isset($_POST['action']) && $_POST['action'] === 'import_csv') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if ($handle !== FALSE) {
            // Baca header kolom
            $headers = fgetcsv($handle, 1000, ";");
            if (!$headers) {
                $headers = fgetcsv($handle, 1000, ","); // Coba pemisah koma
            }
            
            // Fungsi pembantu untuk membersihkan angka dari format rupiah/ribuan excel
            $cleanNumeric = function($value) {
                // Hapus spasi dan simbol mata uang
                $value = preg_replace('/[^\d.,-]/', '', $value);
                
                if (strpos($value, '.') === false && strpos($value, ',') === false) {
                    return (float)$value;
                }
                
                $hasDot = strpos($value, '.') !== false;
                $hasComma = strpos($value, ',') !== false;
                
                if ($hasDot && $hasComma) {
                    if (strrpos($value, '.') > strrpos($value, ',')) {
                        $value = str_replace(',', '', $value);
                    } else {
                        $value = str_replace('.', '', $value);
                        $value = str_replace(',', '.', $value);
                    }
                } elseif ($hasDot) {
                    if (substr_count($value, '.') > 1) {
                        $value = str_replace('.', '', $value);
                    } else {
                        $pos = strlen($value) - strpos($value, '.') - 1;
                        if ($pos === 3) {
                            $value = str_replace('.', '', $value);
                        }
                    }
                } elseif ($hasComma) {
                    if (substr_count($value, ',') > 1) {
                        $value = str_replace(',', '', $value);
                    } else {
                        $pos = strlen($value) - strpos($value, ',') - 1;
                        if ($pos === 3) {
                            $value = str_replace(',', '', $value);
                        } else {
                            $value = str_replace(',', '.', $value);
                        }
                    }
                }
                return (float)$value;
            };

            $inserted = 0;
            $db->beginTransaction();
            try {
                // Skema baris CSV yang diharapkan:
                // Id_Nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan, Tangguan, pendapatan, pendapatan_bersama, Jumlah_pinjaman, jangka_waktu, riwayat_kredit, status_pekerjaan, Asset, label_aktual
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE || ($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) < 13) continue; // Skip jika kolom kurang
                    
                    $id_nasabah = strtoupper(trim($data[0]));
                    $jenis_kelamin = trim($data[1]);
                    $edukasi = trim($data[2]);
                    $status_pernikahan = trim($data[3]);
                    $tangguan = (int)$data[4];
                    $pendapatan = $cleanNumeric($data[5]);
                    $pendapatan_bersama = $cleanNumeric($data[6]);
                    $jumlah_pinjaman = $cleanNumeric($data[7]);
                    $jangka_waktu = (int)$data[8];
                    $riwayat_kredit = (int)$data[9];
                    $status_pekerjaan = trim($data[10]);
                    $asset = trim($data[11]);
                    $label_aktual = trim($data[12]);
                    
                    // Otomatis petakan label lama (Lunas -> Lancar, Tidak Lunas -> Macet)
                    if ($label_aktual === 'Lunas') {
                        $label_aktual = 'Lancar';
                    } elseif ($label_aktual === 'Tidak Lunas') {
                        $label_aktual = 'Macet';
                    }
                    
                    // 1. Pastikan nasabah terdaftar di master data
                    $checkNsb = $db->prepare("SELECT COUNT(*) FROM nasabah WHERE Id_Nasabah = ?");
                    $checkNsb->execute([$id_nasabah]);
                    if ($checkNsb->fetchColumn() == 0) {
                        $insNsb = $db->prepare("INSERT INTO nasabah (Id_Nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan) VALUES (?, ?, ?, ?)");
                        $insNsb->execute([$id_nasabah, $jenis_kelamin, $edukasi, $status_pernikahan]);
                    }
                    
                    // 2. Masukkan ke dataset
                    $stmtDst = $db->prepare("INSERT INTO dataset (
                        Id_nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan, Tangguan, pendapatan, 
                        pendapatan_bersama, Jumlah_pinjaman, jangka_waktu, 
                        riwayat_kredit, status_pekerjaan, Asset, label_aktual
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmtDst->execute([
                        $id_nasabah, $jenis_kelamin, $edukasi, $status_pernikahan, $tangguan, $pendapatan,
                        $pendapatan_bersama, $jumlah_pinjaman, $jangka_waktu,
                        $riwayat_kredit, $status_pekerjaan, $asset, $label_aktual
                    ]);
                    $inserted++;
                }
                $db->commit();
                $success = "Berhasil mengimpor $inserted data dari CSV.";
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Gagal mengimpor CSV: " . $e->getMessage();
            }
            fclose($handle);
        } else {
            $error = "Gagal membuka file CSV.";
        }
    } else {
        $error = "Harap unggah file CSV yang valid.";
    }
}

// --- PEMBACAAN DATA DENGAN PENCARIAN & PAGINATION ---

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Ambil list semua nasabah master untuk dropdown modal
    $nasabahMaster = $db->query("SELECT * FROM nasabah ORDER BY Id_Nasabah ASC")->fetchAll();

    if ($search !== '') {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM dataset WHERE Id_nasabah LIKE ? OR label_aktual LIKE ?");
        $countStmt->execute(["%$search%", "%$search%"]);
        $totalData = $countStmt->fetchColumn();

        $query = "SELECT * FROM dataset WHERE Id_nasabah LIKE ? OR label_aktual LIKE ? ORDER BY Id_kredit DESC LIMIT $limit OFFSET $offset";
        $dataStmt = $db->prepare($query);
        $dataStmt->execute(["%$search%", "%$search%"]);
    } else {
        $totalData = $db->query("SELECT COUNT(*) FROM dataset")->fetchColumn();
        
        $query = "SELECT * FROM dataset ORDER BY Id_kredit DESC LIMIT $limit OFFSET $offset";
        $dataStmt = $db->prepare($query);
        $dataStmt->execute();
    }
    
    $datasetList = $dataStmt->fetchAll();
    $totalPages = ceil($totalData / $limit);
} catch (PDOException $e) {
    die("Gagal membaca data: " . $e->getMessage());
}
?>

<!-- Header Section -->
<div class="page-header">
    <div class="page-title">
        <h2>Kelola Dataset Latih (KNN)</h2>
        <p>Manajemen data histori transaksi kredit nasabah untuk melatih model KNN</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <button class="btn btn-secondary" onclick="openImportModal()">
            <i data-lucide="upload"></i>
            <span>Import CSV</span>
        </button>
        <a href="dataset.php?reset=1" class="btn btn-secondary" onclick="return confirm('Apakah Anda yakin ingin menyetel ulang dataset ke data default bawaan skripsi?')">
            <i data-lucide="refresh-cw"></i>
            <span>Reset Data</span>
        </a>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i data-lucide="plus"></i>
            <span>Tambah Data Latih</span>
        </button>
    </div>
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
    <form action="dataset.php" method="GET" style="display: flex; gap: 12px; align-items: center;">
        <div class="input-wrapper" style="flex-grow: 1;">
            <i data-lucide="search" class="input-icon"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari berdasarkan ID Nasabah atau Label Kelayakan (Lancar/Macet)...">
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($search !== ''): ?>
            <a href="dataset.php" class="btn btn-danger">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Data Table Card -->
<div class="card">
    <div class="card-title-bar">
        <h3>Daftar Data Latih Terdaftar (<?php echo $totalData; ?> baris)</h3>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>ID Kredit</th>
                    <th>ID Nasabah</th>
                    <th>JK</th>
                    <th>Edukasi</th>
                    <th>Status Pernikahan</th>
                    <th>Tanggungan</th>
                    <th>Pendapatan</th>
                    <th>Pendapatan Bersama</th>
                    <th>Jumlah Pinjaman</th>
                    <th>Tenor (Bulan)</th>
                    <th>Riwayat</th>
                    <th>Pekerjaan</th>
                    <th>Asset</th>
                    <th>Label Aktual</th>
                    <th style="width: 100px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($datasetList)): ?>
                    <tr>
                        <td colspan="15" style="text-align: center; color: var(--text-secondary);">Tidak ada data latih.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($datasetList as $row): ?>
                        <tr>
                            <td>#<?php echo $row['Id_kredit']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['Id_nasabah']); ?></strong></td>
                            <td><?php echo ($row['Jenis_Kelamin'] === 'Laki-laki') ? 'L' : 'P'; ?></td>
                            <td><?php echo htmlspecialchars($row['Edukasi']); ?></td>
                            <td><?php echo htmlspecialchars($row['Status_Pernikahan']); ?></td>
                            <td><?php echo $row['Tangguan']; ?> orang</td>
                            <td>Rp<?php echo number_format($row['pendapatan'], 0, ',', '.'); ?></td>
                            <td>Rp<?php echo number_format($row['pendapatan_bersama'], 0, ',', '.'); ?></td>
                            <td>Rp<?php echo number_format($row['Jumlah_pinjaman'], 0, ',', '.'); ?></td>
                            <td><?php echo $row['jangka_waktu']; ?> bln</td>
                            <td>
                                <?php if ($row['riwayat_kredit'] === 1): ?>
                                    <span class="badge badge-success">Baik</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Buruk</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['status_pekerjaan']); ?></td>
                            <td><?php echo htmlspecialchars($row['Asset']); ?></td>
                            <td>
                                <?php if ($row['label_aktual'] === 'Lancar'): ?>
                                    <span class="badge badge-success">Lancar</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Macet</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <button class="btn btn-secondary btn-sm" style="padding: 4px 8px;" 
                                            onclick="openEditModal(
                                                '<?php echo $row['Id_kredit']; ?>',
                                                '<?php echo htmlspecialchars($row['Id_nasabah']); ?>',
                                                '<?php echo $row['Tangguan']; ?>',
                                                '<?php echo $row['pendapatan']; ?>',
                                                '<?php echo $row['pendapatan_bersama']; ?>',
                                                '<?php echo $row['Jumlah_pinjaman']; ?>',
                                                '<?php echo $row['jangka_waktu']; ?>',
                                                '<?php echo $row['riwayat_kredit']; ?>',
                                                '<?php echo htmlspecialchars($row['status_pekerjaan']); ?>',
                                                '<?php echo htmlspecialchars($row['Asset']); ?>',
                                                '<?php echo htmlspecialchars($row['label_aktual']); ?>'
                                            )">
                                        <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                    <a href="dataset.php?delete=<?php echo $row['Id_kredit']; ?>" 
                                       class="btn btn-danger btn-sm" style="padding: 4px 8px;"
                                       onclick="return confirm('Hapus data latih ini?')">
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
                <a href="dataset.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="page-link <?php echo ($page === $i) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ================= MODAL TAMBAH DATA LATIH ================= -->
<div id="modalAdd" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Tambah Dataset Latih Baru</h3>
            <button class="btn-close" onclick="closeAddModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="dataset.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <!-- Data Nasabah Section -->
                <div style="margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 16px;">
                    <h4 style="color: var(--color-primary); margin-bottom: 12px; font-size: 0.95rem; text-transform: uppercase;">1. Identitas Nasabah</h4>
                    <div class="form-group">
                        <label for="add_id_nasabah">ID Nasabah</label>
                        <div class="input-wrapper">
                            <i data-lucide="user" class="input-icon"></i>
                            <select id="add_id_nasabah" name="id_nasabah" required onchange="updateAddProfile(this)">
                                <option value="" disabled selected>Pilih Nasabah Master</option>
                                <?php foreach ($nasabahMaster as $nsb): ?>
                                    <option value="<?php echo htmlspecialchars($nsb['Id_Nasabah']); ?>" 
                                            data-jk="<?php echo htmlspecialchars($nsb['Jenis_Kelamin']); ?>"
                                            data-edu="<?php echo htmlspecialchars($nsb['Edukasi']); ?>"
                                            data-nikah="<?php echo htmlspecialchars($nsb['Status_Pernikahan']); ?>">
                                        <?php echo htmlspecialchars($nsb['Id_Nasabah']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Jenis Kelamin (Auto)</label>
                            <input type="text" id="add_jk_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);" placeholder="Pilih nasabah dahulu">
                        </div>
                        <div class="form-group">
                            <label>Pendidikan (Auto)</label>
                            <input type="text" id="add_edu_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);" placeholder="Pilih nasabah dahulu">
                        </div>
                        <div class="form-group">
                            <label>Status Pernikahan (Auto)</label>
                            <input type="text" id="add_nikah_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);" placeholder="Pilih nasabah dahulu">
                        </div>
                    </div>
                </div>

                <!-- Parameter Transaksi Section -->
                <div>
                    <h4 style="color: var(--color-primary); margin-bottom: 12px; font-size: 0.95rem; text-transform: uppercase;">2. Atribut Keuangan & Kredit</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_tangguan">Jumlah Tanggungan</label>
                            <div class="input-wrapper">
                                <i data-lucide="users" class="input-icon"></i>
                                <input type="number" id="add_tangguan" name="tangguan" min="0" max="15" value="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="add_status_pekerjaan">Status Pekerjaan</label>
                            <div class="input-wrapper">
                                <i data-lucide="briefcase" class="input-icon"></i>
                                <select id="add_status_pekerjaan" name="status_pekerjaan" required>
                                    <option value="Bekerja">Bekerja</option>
                                    <option value="Tidak Bekerja">Tidak Bekerja</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_pendapatan">Pendapatan Bulanan (Rp)</label>
                            <div class="input-wrapper">
                                <i data-lucide="wallet" class="input-icon"></i>
                                <input type="number" id="add_pendapatan" name="pendapatan" min="0" placeholder="Contoh: 5000000" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="add_pendapatan_bersama">Pendapatan Bersama Pasangan (Rp)</label>
                            <div class="input-wrapper">
                                <i data-lucide="coins" class="input-icon"></i>
                                <input type="number" id="add_pendapatan_bersama" name="pendapatan_bersama" min="0" value="0" placeholder="Isi 0 jika tidak ada" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_jumlah_pinjaman">Jumlah Pinjaman (Rp)</label>
                            <div class="input-wrapper">
                                <i data-lucide="banknote" class="input-icon"></i>
                                <input type="number" id="add_jumlah_pinjaman" name="jumlah_pinjaman" min="0" placeholder="Contoh: 25000000" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="add_jangka_waktu">Jangka Waktu / Tenor (Bulan)</label>
                            <div class="input-wrapper">
                                <i data-lucide="calendar" class="input-icon"></i>
                                <input type="number" id="add_jangka_waktu" name="jangka_waktu" min="1" placeholder="Contoh: 12" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_riwayat_kredit">Riwayat Kredit</label>
                            <div class="input-wrapper">
                                <i data-lucide="file-check" class="input-icon"></i>
                                <select id="add_riwayat_kredit" name="riwayat_kredit" required>
                                    <option value="1">Baik (Tidak pernah menunggak)</option>
                                    <option value="0">Buruk (Pernah menunggak/macet)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <labelSquare for="add_asset">Kepemilikan Aset</labelSquare>
                            <div class="input-wrapper">
                                <i data-lucide="home" class="input-icon"></i>
                                <select id="add_asset" name="asset" required>
                                    <option value="Standar">Standar (Ada rumah/tanah/mobil)</option>
                                    <option value="Tidak Memiliki Aset">Tidak Memiliki Aset</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label for="add_label_aktual" style="font-weight:700; color:#fff;">Label Aktual (Target Kredit)</label>
                        <div class="input-wrapper">
                            <i data-lucide="award" class="input-icon"></i>
                            <select id="add_label_aktual" name="label_aktual" required style="border-color: var(--color-teal); background: rgba(13,148,136,0.05);">
                                <option value="Lancar">Lancar (Kelayakan Baik)</option>
                                <option value="Macet">Macet (Berisiko Macet)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Ke Dataset</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL EDIT DATA LATIH ================= -->
<div id="modalEdit" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Edit Dataset Latih (#<span id="edit_id_title"></span>)</h3>
            <button class="btn-close" onclick="closeEditModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="dataset.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id_kredit" name="id_kredit">
            <div class="modal-body">
                <!-- Data Nasabah Section -->
                <div style="margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 16px;">
                    <h4 style="color: var(--color-primary); margin-bottom: 12px; font-size: 0.95rem; text-transform: uppercase;">1. Identitas Nasabah</h4>
                    <div class="form-group">
                        <label for="edit_id_nasabah">ID Nasabah</label>
                        <div class="input-wrapper">
                            <i data-lucide="user" class="input-icon"></i>
                            <select id="edit_id_nasabah" name="id_nasabah" required onchange="updateEditProfile(this)">
                                <?php foreach ($nasabahMaster as $nsb): ?>
                                    <option value="<?php echo htmlspecialchars($nsb['Id_Nasabah']); ?>" 
                                            data-jk="<?php echo htmlspecialchars($nsb['Jenis_Kelamin']); ?>"
                                            data-edu="<?php echo htmlspecialchars($nsb['Edukasi']); ?>"
                                            data-nikah="<?php echo htmlspecialchars($nsb['Status_Pernikahan']); ?>">
                                        <?php echo htmlspecialchars($nsb['Id_Nasabah']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Jenis Kelamin (Auto)</label>
                            <input type="text" id="edit_jk_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);">
                        </div>
                        <div class="form-group">
                            <label>Pendidikan (Auto)</label>
                            <input type="text" id="edit_edu_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);">
                        </div>
                        <div class="form-group">
                            <label>Status Pernikahan (Auto)</label>
                            <input type="text" id="edit_nikah_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);">
                        </div>
                    </div>
                </div>

                <!-- Parameter Transaksi Section -->
                <div>
                    <h4 style="color: var(--color-primary); margin-bottom: 12px; font-size: 0.95rem; text-transform: uppercase;">2. Atribut Keuangan & Kredit</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_tangguan">Jumlah Tanggungan</label>
                            <div class="input-wrapper">
                                <i data-lucide="users" class="input-icon"></i>
                                <input type="number" id="edit_tangguan" name="tangguan" min="0" max="15" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_status_pekerjaan">Status Pekerjaan</label>
                            <div class="input-wrapper">
                                <i data-lucide="briefcase" class="input-icon"></i>
                                <select id="edit_status_pekerjaan" name="status_pekerjaan" required>
                                    <option value="Bekerja">Bekerja</option>
                                    <option value="Tidak Bekerja">Tidak Bekerja</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_pendapatan">Pendapatan Bulanan (Rp)</label>
                            <div class="input-wrapper">
                                <i data-lucide="wallet" class="input-icon"></i>
                                <input type="number" id="edit_pendapatan" name="pendapatan" min="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_pendapatan_bersama">Pendapatan Bersama Pasangan (Rp)</label>
                            <div class="input-wrapper">
                                <i data-lucide="coins" class="input-icon"></i>
                                <input type="number" id="edit_pendapatan_bersama" name="pendapatan_bersama" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_jumlah_pinjaman">Jumlah Pinjaman (Rp)</label>
                            <div class="input-wrapper">
                                <i data-lucide="banknote" class="input-icon"></i>
                                <input type="number" id="edit_jumlah_pinjaman" name="jumlah_pinjaman" min="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_jangka_waktu">Jangka Waktu / Tenor (Bulan)</label>
                            <div class="input-wrapper">
                                <i data-lucide="calendar" class="input-icon"></i>
                                <input type="number" id="edit_jangka_waktu" name="jangka_waktu" min="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_riwayat_kredit">Riwayat Kredit</label>
                            <div class="input-wrapper">
                                <i data-lucide="file-check" class="input-icon"></i>
                                <select id="edit_riwayat_kredit" name="riwayat_kredit" required>
                                    <option value="1">Baik (Tidak pernah menunggak)</option>
                                    <option value="0">Buruk (Pernah menunggak/macet)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_asset">Kepemilikan Aset</label>
                            <div class="input-wrapper">
                                <i data-lucide="home" class="input-icon"></i>
                                <select id="edit_asset" name="asset" required>
                                    <option value="Standar">Standar (Ada rumah/tanah/mobil)</option>
                                    <option value="Tidak Memiliki Aset">Tidak Memiliki Aset</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label for="edit_label_aktual" style="font-weight:700; color:#fff;">Label Aktual (Target Kredit)</label>
                        <div class="input-wrapper">
                            <i data-lucide="award" class="input-icon"></i>
                            <select id="edit_label_aktual" name="label_aktual" required style="border-color: var(--color-teal); background: rgba(13,148,136,0.05);">
                                <option value="Lancar">Lancar (Kelayakan Baik)</option>
                                <option value="Macet">Macet (Berisiko Macet)</option>
                            </select>
                        </div>
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

<!-- ================= MODAL IMPORT CSV ================= -->
<div id="modalImport" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Unggah Data Latih dari CSV</h3>
            <button class="btn-close" onclick="closeImportModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="dataset.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import_csv">
            <div class="modal-body">
                <p style="font-size:0.9rem; color: var(--text-secondary); margin-bottom: 16px;">
                    Unggah file CSV dengan separator titik koma (<code>;</code>) atau koma (<code>,</code>). Jika ID Nasabah pada file CSV belum terdaftar pada Master Nasabah, sistem akan menambahkannya secara otomatis.
                </p>
                <div class="form-group">
                    <label for="csv_file">Pilih File CSV</label>
                    <div class="input-wrapper">
                        <i data-lucide="file-text" class="input-icon"></i>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required style="padding-top: 10px; padding-bottom: 10px;">
                    </div>
                </div>
                <div class="formula-card" style="margin-top: 16px; padding: 12px;">
                    <h5 style="color:#fff; font-size:0.85rem; margin-bottom:6px;">Format Kolom CSV (Baris Header Harus Ada):</h5>
                    <code style="font-size:0.75rem; color: var(--text-secondary); display:block; word-break:break-all;">
                        Id_Nasabah,Jenis_Kelamin,Edukasi,Status_Pernikahan,Tangguan,pendapatan,pendapatan_bersama,Jumlah_pinjaman,jangka_waktu,riwayat_kredit,status_pekerjaan,Asset,label_aktual
                    </code>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Mulai Impor</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- MODAL TAMBAH ---
    function openAddModal() {
        document.getElementById('modalAdd').classList.add('show');
    }
    function closeAddModal() {
        document.getElementById('modalAdd').classList.remove('show');
    }
    function updateAddProfile(selectElem) {
        let opt = selectElem.options[selectElem.selectedIndex];
        let jk = opt.getAttribute('data-jk');
        let edu = opt.getAttribute('data-edu');
        let nikah = opt.getAttribute('data-nikah');
        
        document.getElementById('add_jk_show').value = jk || '';
        document.getElementById('add_edu_show').value = edu || '';
        document.getElementById('add_nikah_show').value = nikah || '';
    }

    // --- MODAL EDIT ---
    function openEditModal(idKredit, idNasabah, tangguan, pendapatan, pendapatanBersama, jumlahPinjaman, jangkaWaktu, riwayatKredit, statusPekerjaan, asset, labelAktual) {
        document.getElementById('edit_id_title').innerText = idKredit;
        document.getElementById('edit_id_kredit').value = idKredit;
        
        let selectNsb = document.getElementById('edit_id_nasabah');
        selectNsb.value = idNasabah;
        
        // Triger update read-only gender, edukasi dan status pernikahan
        updateEditProfile(selectNsb);

        document.getElementById('edit_tangguan').value = tangguan;
        document.getElementById('edit_pendapatan').value = pendapatan;
        document.getElementById('edit_pendapatan_bersama').value = pendapatanBersama;
        document.getElementById('edit_jumlah_pinjaman').value = jumlahPinjaman;
        document.getElementById('edit_jangka_waktu').value = jangkaWaktu;
        document.getElementById('edit_riwayat_kredit').value = riwayatKredit;
        document.getElementById('edit_status_pekerjaan').value = statusPekerjaan;
        document.getElementById('edit_asset').value = asset;
        document.getElementById('edit_label_aktual').value = labelAktual;

        document.getElementById('modalEdit').classList.add('show');
    }
    function closeEditModal() {
        document.getElementById('modalEdit').classList.remove('show');
    }
    function updateEditProfile(selectElem) {
        let opt = selectElem.options[selectElem.selectedIndex];
        let jk = opt.getAttribute('data-jk');
        let edu = opt.getAttribute('data-edu');
        let nikah = opt.getAttribute('data-nikah');
        
        document.getElementById('edit_jk_show').value = jk || '';
        document.getElementById('edit_edu_show').value = edu || '';
        document.getElementById('edit_nikah_show').value = nikah || '';
    }

    // --- MODAL IMPORT ---
    function openImportModal() {
        document.getElementById('modalImport').classList.add('show');
    }
    function closeImportModal() {
        document.getElementById('modalImport').classList.remove('show');
    }

    // Overlay Close
    window.onclick = function(event) {
        let mAdd = document.getElementById('modalAdd');
        let mEdit = document.getElementById('modalEdit');
        let mImp = document.getElementById('modalImport');
        if (event.target == mAdd) closeAddModal();
        if (event.target == mEdit) closeEditModal();
        if (event.target == mImp) closeImportModal();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
