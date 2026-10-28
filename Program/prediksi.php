<?php
// prediksi.php
// Simulasi perhitungan dan prediksi kredit baru dengan KNN

require_once 'includes/header.php';
require_once 'knn.php';

$db = getDB();
$error = '';
$result = null;

// Ambil list semua nasabah master untuk dropdown
try {
    $nasabahMaster = $db->query("SELECT * FROM nasabah ORDER BY Id_Nasabah ASC")->fetchAll();
} catch (PDOException $e) {
    die("Gagal membaca master nasabah: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_nasabah = $_POST['id_nasabah'];
    $tangguan = (int)$_POST['tangguan'];
    $pendapatan = (float)$_POST['pendapatan'];
    $pendapatan_bersama = (float)$_POST['pendapatan_bersama'];
    $jumlah_pinjaman = (float)$_POST['jumlah_pinjaman'];
    $jangka_waktu = (int)$_POST['jangka_waktu'];
    $riwayat_kredit = (int)$_POST['riwayat_kredit'];
    $status_pekerjaan = $_POST['status_pekerjaan'];
    $asset = $_POST['asset'];
    $k_value = (int)$_POST['k_value'];

    if (!empty($id_nasabah) && isset($_POST['tangguan'])) {
        try {
            // Ambil profile asli nasabah dari master
            $nsbStmt = $db->prepare("SELECT Jenis_Kelamin, Edukasi, Status_Pernikahan FROM nasabah WHERE Id_Nasabah = ?");
            $nsbStmt->execute([$id_nasabah]);
            $nsb = $nsbStmt->fetch();

            if ($nsb) {
                $jenis_kelamin = $nsb['Jenis_Kelamin'];
                $edukasi = $nsb['Edukasi'];
                $status_pernikahan = $nsb['Status_Pernikahan'];

                // Buat data uji
                $testData = [
                    'Id_nasabah' => $id_nasabah,
                    'Jenis_Kelamin' => $jenis_kelamin,
                    'Edukasi' => $edukasi,
                    'Status_Pernikahan' => $status_pernikahan,
                    'Tangguan' => $tangguan,
                    'pendapatan' => $pendapatan,
                    'pendapatan_bersama' => $pendapatan_bersama,
                    'Jumlah_pinjaman' => $jumlah_pinjaman,
                    'jangka_waktu' => $jangka_waktu,
                    'riwayat_kredit' => $riwayat_kredit,
                    'status_pekerjaan' => $status_pekerjaan,
                    'Asset' => $asset
                ];

                // Jalankan KNN
                $knn = new KNN($db);
                $result = $knn->predict($testData, $k_value);

                if (isset($result['error'])) {
                    $error = $result['error'];
                    $result = null;
                } else {
                    // Simpan hasil ke tabel riwayat prediksi
                    $stmtSave = $db->prepare("INSERT INTO riwayat (
                        Id_nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan, Tangguan, pendapatan, 
                        pendapatan_bersama, Jumlah_pinjaman, jangka_waktu, 
                        riwayat_kredit, status_pekerjaan, Asset, k_value, hasil_prediksi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmtSave->execute([
                        $id_nasabah, $jenis_kelamin, $edukasi, $status_pernikahan, $tangguan, $pendapatan,
                        $pendapatan_bersama, $jumlah_pinjaman, $jangka_waktu,
                        $riwayat_kredit, $status_pekerjaan, $asset, $k_value, $result['prediksi']
                    ]);
                    $result['Id_kredit'] = $db->lastInsertId();
                }
            } else {
                $error = "Nasabah tidak terdaftar!";
            }
        } catch (PDOException $e) {
            $error = "Kesalahan penyimpanan riwayat: " . $e->getMessage();
            $result = null;
        }
    } else {
        $error = "Mohon lengkapi semua isian form!";
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h2>Simulasi Prediksi Kredit</h2>
        <p>Input data nasabah baru untuk menguji kelayakan kredit menggunakan KNN</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i data-lucide="alert-circle" class="alert-icon"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<div class="dashboard-grid" style="grid-template-columns: 1fr 2fr; gap: 32px; align-items: start;">
    <!-- Form Input Data Uji -->
    <div class="card">
        <div class="card-title-bar">
            <h3>Form Pengujian Baru</h3>
        </div>
        <form action="prediksi.php" method="POST">
            <!-- Nasabah Master Dropdown -->
            <div class="form-group">
                <label for="id_nasabah">Pilih Nasabah Master</label>
                <div class="input-wrapper">
                    <i data-lucide="user" class="input-icon"></i>
                    <select id="id_nasabah" name="id_nasabah" required onchange="updateProfile(this)">
                        <option value="" disabled selected>Pilih Nasabah</option>
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

            <!-- Read Only Profiling -->
            <div class="form-group">
                <label>Jenis Kelamin (Auto)</label>
                <div class="input-wrapper">
                    <i data-lucide="info" class="input-icon"></i>
                    <input type="text" id="jk_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);" placeholder="Jenis Kelamin">
                </div>
            </div>

            <div class="form-group">
                <label>Pendidikan Terakhir (Auto)</label>
                <div class="input-wrapper">
                    <i data-lucide="info" class="input-icon"></i>
                    <input type="text" id="edu_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);" placeholder="Pendidikan">
                </div>
            </div>

            <div class="form-group">
                <label>Status Pernikahan (Auto)</label>
                <div class="input-wrapper">
                    <i data-lucide="info" class="input-icon"></i>
                    <input type="text" id="nikah_show" readonly style="background: rgba(255,255,255,0.02); color: var(--text-muted);" placeholder="Status Pernikahan">
                </div>
            </div>

            <!-- Tanggungan & Pekerjaan -->
            <div class="form-group">
                <label for="tangguan">Jumlah Tanggungan</label>
                <div class="input-wrapper">
                    <i data-lucide="users" class="input-icon"></i>
                    <input type="number" id="tangguan" name="tangguan" min="0" max="15" value="0" required>
                </div>
            </div>

            <div class="form-group">
                <label for="status_pekerjaan">Status Pekerjaan</label>
                <div class="input-wrapper">
                    <i data-lucide="briefcase" class="input-icon"></i>
                    <select id="status_pekerjaan" name="status_pekerjaan" required>
                        <option value="Bekerja">Bekerja</option>
                        <option value="Tidak Bekerja">Tidak Bekerja</option>
                    </select>
                </div>
            </div>

            <!-- Financials -->
            <div class="form-group">
                <label for="pendapatan">Pendapatan Bulanan (Rp)</label>
                <div class="input-wrapper">
                    <i data-lucide="wallet" class="input-icon"></i>
                    <input type="number" id="pendapatan" name="pendapatan" min="0" placeholder="Contoh: 5000000" required>
                </div>
            </div>

            <div class="form-group">
                <label for="pendapatan_bersama">Pendapatan Bersama (Rp)</label>
                <div class="input-wrapper">
                    <i data-lucide="coins" class="input-icon"></i>
                    <input type="number" id="pendapatan_bersama" name="pendapatan_bersama" min="0" value="0" required>
                </div>
            </div>

            <div class="form-group">
                <label for="jumlah_pinjaman">Jumlah Pinjaman (Rp)</label>
                <div class="input-wrapper">
                    <i data-lucide="banknote" class="input-icon"></i>
                    <input type="number" id="jumlah_pinjaman" name="jumlah_pinjaman" min="0" placeholder="Contoh: 20000000" required>
                </div>
            </div>

            <div class="form-group">
                <label for="jangka_waktu">Tenor / Jangka Waktu (Bulan)</label>
                <div class="input-wrapper">
                    <i data-lucide="calendar" class="input-icon"></i>
                    <input type="number" id="jangka_waktu" name="jangka_waktu" min="1" placeholder="Contoh: 12" required>
                </div>
            </div>

            <!-- Kredit & Asset -->
            <div class="form-group">
                <label for="riwayat_kredit">Riwayat Kredit</label>
                <div class="input-wrapper">
                    <i data-lucide="file-check" class="input-icon"></i>
                    <select id="riwayat_kredit" name="riwayat_kredit" required>
                        <option value="1">Baik</option>
                        <option value="0">Buruk</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="asset">Kepemilikan Aset</label>
                <div class="input-wrapper">
                    <i data-lucide="home" class="input-icon"></i>
                    <select id="asset" name="asset" required>
                        <option value="Standar">Standar</option>
                        <option value="Tidak Memiliki Aset">Tidak Memiliki Aset</option>
                    </select>
                </div>
            </div>

            <!-- Nilai K -->
            <div class="form-group">
                <label for="k_value">Pilih Nilai K (Tetangga Terdekat)</label>
                <div class="input-wrapper">
                    <i data-lucide="hash" class="input-icon"></i>
                    <select id="k_value" name="k_value" required style="border-color: var(--color-secondary);">
                        <option value="3" selected>K = 3</option>
                        <option value="5">K = 5</option>
                        <option value="7">K = 7</option>
                        <option value="9">K = 9</option>
                        <option value="11">K = 11</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 16px;">
                <i data-lucide="play"></i>
                <span>Proses Prediksi</span>
            </button>
        </form>
    </div>

    <!-- Tampilan Perhitungan KNN -->
    <div>
        <?php if ($result === null): ?>
            <div class="card" style="text-align: center; padding: 60px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px;">
                <i data-lucide="binary" style="width: 64px; height: 64px; color: var(--text-muted);"></i>
                <h3 style="color:#fff;">Belum Ada Pengujian</h3>
                <p style="color: var(--text-secondary); max-width: 320px; font-size: 0.9rem;">
                    Silakan isi formulir di sebelah kiri dan klik <strong>Proses Prediksi</strong> untuk melihat hasil simulasi perhitungan matematis algoritma KNN.
                </p>
            </div>
        <?php else: ?>
            <div class="calculation-block">
                <!-- Hasil Akhir Card -->
                <div class="card" style="border-color: var(--color-primary);">
                    <div class="voting-result-card">
                        <h4 style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Hasil Prediksi Kelayakan</h4>
                        
                        <?php if ($result['prediksi'] === 'Lancar'): ?>
                            <div class="result-verdict verdict-lunas">KREDIT LAYAK (LANCAR)</div>
                        <?php else: ?>
                            <div class="result-verdict verdict-tidak-lunas">KREDIT BERISIKO (MACET)</div>
                        <?php endif; ?>

                        <div class="credentials-hint" style="margin-top: 12px; background: rgba(255,255,255,0.02);">
                            <i data-lucide="bar-chart-2" class="hint-icon"></i>
                            <span>Hasil voting dari K = <?php echo $result['k']; ?> tetangga terdekat: 
                                  <strong>Lancar (<?php echo $result['votes']['Lancar']; ?>)</strong> vs 
                                  <strong>Macet (<?php echo $result['votes']['Macet']; ?>)</strong>
                            </span>
                        </div>

                        <?php if (isset($result['Id_kredit'])): ?>
                        <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                            <a href="cetak_laporan.php?id=<?php echo $result['Id_kredit']; ?>" 
                               class="btn btn-primary" style="padding: 10px 20px; font-size: 0.9rem;" target="_blank">
                                <i data-lucide="printer"></i>
                                <span>Cetak Laporan PDF</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rumus KNN -->
                <div class="step-card">
                    <span class="step-number">Teori Rumus</span>
                    <h3>Rumus Jarak Euclidean</h3>
                    <p>Rumus ini digunakan untuk menghitung jarak kesamaan antara data uji baru (x) dan data latih (y) setelah data tersebut dinormalisasi:</p>
                    <div class="formula-card">
                        <div class="formula-latex">d(x, y) = √ Σ (x_i - y_i)²</div>
                        <div style="font-size:0.78rem; color: var(--text-muted); text-align:center; margin-top:6px;">
                            di mana i merupakan indeks setiap fitur (terdapat 11 fitur penentu kelayakan kredit).
                        </div>
                    </div>
                </div>

                <!-- Langkah 1: Enkoding Uji -->
                <div class="step-card">
                    <span class="step-number">Langkah 1</span>
                    <h3>Enkoding & Nilai Numerik Data Uji</h3>
                    <p>Sistem merubah variabel kategorikal data uji menjadi representasi numerik biner (0/1):</p>
                    
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Fitur</th>
                                    <th>Input Asli</th>
                                    <th>Representasi Numerik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['data_uji_asli'] as $fitur => $val): if ($fitur === 'Id_nasabah') continue; ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($fitur); ?></strong></td>
                                        <td><?php echo htmlspecialchars($val); ?></td>
                                        <td><span class="badge badge-info"><?php echo (float)KNN::encodeValue($fitur, $val); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Langkah 2: Normalisasi -->
                <div class="step-card">
                    <span class="step-number">Langkah 2</span>
                    <h3>Normalisasi Min-Max Data Uji</h3>
                    <p>Normalisasi dilakukan agar fitur berskala besar (misal: Pendapatan jutaan) tidak mendominasi fitur berskala kecil (misal: Tanggungan atau Biner 0/1):</p>
                    <div class="formula-card" style="padding:12px 20px; font-size:0.85rem; color: var(--text-secondary); margin-bottom:12px;">
                        Formula: <strong>x_normal = (x_asli - min) / (max - min)</strong>
                    </div>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Fitur</th>
                                    <th>Nilai Numerik</th>
                                    <th>Min Data Latih</th>
                                    <th>Max Data Latih</th>
                                    <th>Hasil Normalisasi (0 s/d 1)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['data_uji_normalisasi'] as $fitur => $normVal): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($fitur); ?></strong></td>
                                        <td><?php echo (float)KNN::encodeValue($fitur, $result['data_uji_asli'][$fitur]); ?></td>
                                        <td><?php echo (float)$result['min_max'][$fitur]['min']; ?></td>
                                        <td><?php echo (float)round($result['min_max'][$fitur]['max'], 4); ?></td>
                                        <td><span class="badge badge-success"><?php echo round($normVal, 5); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Langkah 3 & 4: Tabel Jarak -->
                <div class="step-card">
                    <span class="step-number">Langkah 3 & 4</span>
                    <h3>Perhitungan Jarak Euclidean & Pengurutan Terdekat</h3>
                    <p>Sistem menghitung jarak ke seluruh data latih, mengurutkannya dari yang terkecil ke terbesar, dan mengambil <strong>K = <?php echo $result['k']; ?></strong> tetangga terdekat (warna ungu) sebagai penentu keputusan:</p>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Peringkat</th>
                                    <th>ID Kredit</th>
                                    <th>ID Nasabah</th>
                                    <th>Jarak Euclidean</th>
                                    <th>Label Aktual</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($result['semua_jarak'] as $rowJarak):
                                    $isNeighbor = false;
                                    foreach ($result['tetangga_terdekat'] as $t) {
                                        if ($t['Id_kredit'] === $rowJarak['Id_kredit']) {
                                            $isNeighbor = true;
                                            break;
                                        }
                                    }
                                ?>
                                    <tr class="<?php echo $isNeighbor ? 'row-highlighted' : ''; ?>">
                                        <td><strong>#<?php echo $rank++; ?></strong></td>
                                        <td>#<?php echo $rowJarak['Id_kredit']; ?></td>
                                        <td><?php echo htmlspecialchars($rowJarak['Id_nasabah']); ?></td>
                                        <td><strong><?php echo round($rowJarak['jarak'], 6); ?></strong></td>
                                        <td>
                                            <?php if ($rowJarak['label_aktual'] === 'Lancar'): ?>
                                                <span class="badge badge-success">Lancar</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Macet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isNeighbor): ?>
                                                <span class="text-highlight"><i data-lucide="check" style="width:12px;height:12px;display:inline-block;margin-right:2px;vertical-align:middle;"></i>Tetangga Terdekat</span>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Langkah 5: Voting -->
                <div class="step-card">
                    <span class="step-number">Langkah 5</span>
                    <h3>Voting Hasil Keputusan Akhir</h3>
                    <p>Melakukan perhitungan frekuensi kemunculan kelas terbanyak (modus) di antara tetangga terdekat:</p>
                    
                    <div style="display:flex; justify-content: space-around; margin: 20px 0; gap: 16px;">
                        <div class="card" style="flex:1; text-align:center; border-color: rgba(16, 185, 129, 0.2); background: rgba(16, 185, 129, 0.02);">
                            <div style="color: var(--text-secondary); font-size: 0.8rem; text-transform:uppercase;">Jumlah Label Lancar</div>
                            <div style="font-size:2.5rem; font-weight:700; color: var(--color-success); margin-top:8px;"><?php echo $result['votes']['Lancar']; ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">toko suara kelayakan</div>
                        </div>
                        <div class="card" style="flex:1; text-align:center; border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.02);">
                            <div style="color: var(--text-secondary); font-size: 0.8rem; text-transform:uppercase;">Jumlah Label Macet</div>
                            <div style="font-size:2.5rem; font-weight:700; color: var(--color-danger); margin-top:8px;"><?php echo $result['votes']['Macet']; ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">toko suara berisiko macet</div>
                        </div>
                    </div>

                    <p style="text-align: center; color: var(--text-primary); font-size: 0.95rem; background: rgba(255, 255, 255, 0.02); padding: 12px; border-radius: 12px; border: 1px solid var(--border-light);">
                        Karena jumlah tetangga dengan label 
                        <strong>
                            <?php echo ($result['votes']['Lancar'] >= $result['votes']['Macet']) ? 'Lancar' : 'Macet'; ?>
                        </strong> 
                        lebih banyak, maka data nasabah ini diprediksi masuk ke kelas 
                        <span class="text-highlight" style="font-size:1.05rem;">
                            <?php echo $result['prediksi']; ?>
                        </span>.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function updateProfile(selectElem) {
        let opt = selectElem.options[selectElem.selectedIndex];
        let jk = opt.getAttribute('data-jk');
        let edu = opt.getAttribute('data-edu');
        let nikah = opt.getAttribute('data-nikah');
        
        document.getElementById('jk_show').value = jk || '';
        document.getElementById('edu_show').value = edu || '';
        document.getElementById('nikah_show').value = nikah || '';
    }
</script>

<?php require_once 'includes/footer.php'; ?>
