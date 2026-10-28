<?php
// pengguna.php
// Manajemen Pengguna Sistem (CRUD Users)

require_once 'includes/header.php';

if ($_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini hanya untuk Administrator.'); window.location.href='index.php';</script>";
    exit;
}

$db = getDB();
$error = '';
$success = '';
$currentUserId = $_SESSION['user']['id'];

// --- PROSES CRUD ---

// 1. Tambah Pengguna Baru
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = strtolower(trim($_POST['username']));
    $password = trim($_POST['password']);
    $nama = trim($_POST['nama']);
    $role = $_POST['role'] ?? 'petugas';

    if (!empty($username) && !empty($password) && !empty($nama) && !empty($role)) {
        try {
            // Cek duplikasi username
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $checkStmt->execute([$username]);
            if ($checkStmt->fetchColumn() > 0) {
                $error = "Username '$username' sudah digunakan pengguna lain!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $nama, $role]);
                $success = "Pengguna baru '$nama' dengan username '$username' (Role: $role) berhasil ditambahkan.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi seluruh kolom form!";
    }
}

// 2. Edit Pengguna
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $username = strtolower(trim($_POST['username']));
    $password = trim($_POST['password']);
    $nama = trim($_POST['nama']);
    $role = $_POST['role'] ?? 'petugas';

    if (!empty($id) && !empty($username) && !empty($nama) && !empty($role)) {
        try {
            // Cek duplikasi username untuk ID yang berbeda
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$username, $id]);
            if ($checkStmt->fetchColumn() > 0) {
                $error = "Username '$username' sudah digunakan pengguna lain!";
            } else {
                if (!empty($password)) {
                    // Jika password diisi, ubah password juga
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, nama = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $hashedPassword, $nama, $role, $id]);
                } else {
                    // Jika password dikosongkan, pertahankan password lama
                    $stmt = $db->prepare("UPDATE users SET username = ?, nama = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $nama, $role, $id]);
                }

                // Jika user mengedit akunnya sendiri yang sedang aktif, update session
                if ($id === $currentUserId) {
                    $_SESSION['user']['nama'] = $nama;
                    $_SESSION['user']['username'] = $username;
                    $_SESSION['user']['role'] = $role;
                }

                $success = "Profil pengguna '$nama' berhasil diperbarui.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi seluruh kolom form!";
    }
}

// 3. Hapus Pengguna
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    
    // Validasi: Tidak boleh menghapus diri sendiri
    if ($id_del === $currentUserId) {
        $error = "Gagal menghapus: Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan aktif!";
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_del]);
            $success = "Pengguna berhasil dihapus dari sistem.";
        } catch (PDOException $e) {
            $error = "Gagal menghapus pengguna: " . $e->getMessage();
        }
    }
}

// --- BACA DATA PENGGUNA ---
try {
    $stmtUsers = $db->query("SELECT id, username, nama, role, created_at FROM users ORDER BY nama ASC");
    $usersList = $stmtUsers->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat daftar pengguna: " . $e->getMessage());
}
?>

<!-- Header Section -->
<div class="page-header">
    <div class="page-title">
        <h2>Kelola Pengguna Sistem</h2>
        <p>Manajemen akun administrator yang memiliki hak akses ke sistem prediksi</p>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i data-lucide="user-plus"></i>
        <span>Tambah Pengguna</span>
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

<!-- Users Table Card -->
<div class="card">
    <div class="card-title-bar">
        <h3>Daftar Administrator Terdaftar (<?php echo count($usersList); ?> orang)</h3>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Hak Akses</th>
                    <th>Tanggal Registrasi</th>
                    <th style="width: 150px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($usersList as $row): 
                    $isSelf = ($row['id'] === $currentUserId);
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['nama']); ?></strong>
                            <?php if ($isSelf): ?>
                                <span class="badge badge-success" style="font-size:0.65rem; margin-left: 6px;">Aktif Saya</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($row['username']); ?></code></td>
                        <td>
                            <?php if ($row['role'] === 'admin'): ?>
                                <span class="badge badge-success">Administrator</span>
                            <?php else: ?>
                                <span class="badge badge-info">Petugas</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <button class="btn btn-secondary btn-sm" style="padding: 6px 10px;" 
                                        onclick="openEditModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['nama']); ?>', '<?php echo $row['role']; ?>')">
                                    <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                                </button>
                                <?php if (!$isSelf): ?>
                                    <a href="pengguna.php?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-sm" style="padding: 6px 10px;"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus administrator ini?')">
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" style="padding: 6px 10px; opacity: 0.3; cursor: not-allowed;" disabled>
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODAL TAMBAH PENGGUNA ================= -->
<div id="modalAdd" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Registrasi Administrator Baru</h3>
            <button class="btn-close" onclick="closeAddModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="pengguna.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_nama">Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" id="add_nama" name="nama" placeholder="Contoh: Budi Santoso" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_username">Username</label>
                    <div class="input-wrapper">
                        <i data-lucide="at-sign" class="input-icon"></i>
                        <input type="text" id="add_username" name="username" placeholder="Contoh: budi12" required pattern="[A-Za-z0-9_]+" title="Hanya alfanumerik dan garis bawah">
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_password">Password</label>
                    <div class="input-wrapper">
                        <i data-lucide="key-round" class="input-icon"></i>
                        <input type="password" id="add_password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_role">Hak Akses (Role)</label>
                    <div class="input-wrapper">
                        <i data-lucide="shield" class="input-icon"></i>
                        <select id="add_role" name="role" required>
                            <option value="petugas" selected>Petugas</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Daftarkan User</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL EDIT PENGGUNA ================= -->
<div id="modalEdit" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Edit Profil Administrator</h3>
            <button class="btn-close" onclick="closeEditModal()"><i data-lucide="x"></i></button>
        </div>
        <form action="pengguna.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_nama">Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <div class="input-wrapper">
                        <i data-lucide="at-sign" class="input-icon"></i>
                        <input type="text" id="edit_username" name="username" required pattern="[A-Za-z0-9_]+" title="Hanya alfanumerik dan garis bawah">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 12px; border-top: 1px dashed var(--border-light); padding-top: 16px;">
                    <label for="edit_role">Hak Akses (Role)</label>
                    <div class="input-wrapper">
                        <i data-lucide="shield" class="input-icon"></i>
                        <select id="edit_role" name="role" required>
                            <option value="petugas">Petugas</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 12px; border-top: 1px dashed var(--border-light); padding-top: 16px;">
                    <label for="edit_password">Password Baru (Opsional)</label>
                    <div class="input-wrapper">
                        <i data-lucide="key-round" class="input-icon"></i>
                        <input type="password" id="edit_password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                    </div>
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                        Isi hanya jika ingin mengganti sandi lama Anda.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Profil</button>
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
    function openEditModal(id, username, nama, role) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_password').value = ''; // Reset input password
        document.getElementById('modalEdit').classList.add('show');
    }
    function closeEditModal() {
        document.getElementById('modalEdit').classList.remove('show');
    }

    // Modal Overlay Close
    window.onclick = function(event) {
        let mAdd = document.getElementById('modalAdd');
        let mEdit = document.getElementById('modalEdit');
        if (event.target == mAdd) closeAddModal();
        if (event.target == mEdit) closeEditModal();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
