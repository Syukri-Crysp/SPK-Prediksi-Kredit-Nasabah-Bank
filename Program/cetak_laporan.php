<?php
// cetak_laporan.php
// Halaman cetak laporan hasil analisa prediksi kelayakan kredit dalam bentuk PDF/Print

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman: Pastikan user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$error = '';
$row = null;

// Ambil ID Kredit dari parameter URL
if (isset($_GET['id'])) {
    $id_kredit = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("SELECT r.*, n.Jenis_Kelamin as jk_master, n.Edukasi as edu_master, n.Status_Pernikahan as nikah_master 
                             FROM riwayat r 
                             LEFT JOIN nasabah n ON r.Id_nasabah = n.Id_Nasabah 
                             WHERE r.Id_kredit = ?");
        $stmt->execute([$id_kredit]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = "Data log riwayat dengan ID Kredit #$id_kredit tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error = "Gagal memuat data laporan: " . $e->getMessage();
    }
} else {
    $error = "Parameter ID Kredit tidak valid atau tidak disediakan.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kelayakan Kredit - <?php echo $row ? htmlspecialchars($row['Id_nasabah']) : 'Error'; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,400&family=Courier+Prime&display=swap" rel="stylesheet">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- html2pdf.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --bg-dark: #0b0f19;
            --bg-card: rgba(17, 24, 39, 0.7);
            --border-light: rgba(255, 255, 255, 0.08);
            --color-primary: #f15a22; /* BNI Orange */
            --color-secondary: #005e6a; /* BNI Teal */
            --text-gray: #cbd5e1;
            --text-dark: #1e293b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 50px;
            overflow-x: hidden;
            position: relative;
        }

        /* Background glow blobs */
        .glow-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(140px);
            opacity: 0.15;
        }
        .blob-1 {
            width: 400px;
            height: 400px;
            background: var(--color-primary);
            top: 10%;
            left: -10%;
        }
        .blob-2 {
            width: 500px;
            height: 500px;
            background: var(--color-secondary);
            bottom: 10%;
            right: -10%;
        }

        /* Sticky Control Bar */
        .control-bar {
            position: sticky;
            top: 20px;
            width: 100%;
            max-width: 800px;
            background: rgba(17, 24, 39, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .control-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .control-title h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
        }
        .control-title p {
            font-size: 0.78rem;
            color: var(--text-gray);
        }

        .control-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-report {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-back {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid var(--border-light);
        }
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.12);
        }
        .btn-print {
            background: var(--color-secondary);
            color: #fff;
        }
        .btn-print:hover {
            background: #004b55;
            transform: translateY(-1px);
        }
        .btn-pdf {
            background: var(--color-primary);
            color: #fff;
        }
        .btn-pdf:hover {
            background: #d84c17;
            transform: translateY(-1px);
        }

        /* Error state */
        .error-card {
            width: 100%;
            max-width: 800px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            margin-top: 100px;
        }
        .error-card h3 {
            color: #ef4444;
            margin-bottom: 12px;
        }
        .error-card a {
            margin-top: 20px;
        }

        /* A4 Paper Layout (preview on screen) */
        .report-paper {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            color: #000;
            padding: 25mm 20mm;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            margin-bottom: 30px;
        }

        /* Kop Surat BNI */
        .kop-surat {
            display: flex;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 3px double #000;
            margin-bottom: 24px;
            width: 100%;
        }

        .kop-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 20px;
            border: 1px solid #ddd;
        }

        .kop-text {
            flex-grow: 1;
            text-align: center;
            margin-right: 80px; /* offset the logo width for perfect center centering */
        }
        .kop-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0b0f19;
            margin-bottom: 4px;
        }
        .kop-text h3 {
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--color-secondary);
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .kop-text p {
            font-size: 0.72rem;
            color: #475569;
            line-height: 1.4;
        }

        /* Report Header Title */
        .report-title-section {
            text-align: center;
            margin-bottom: 24px;
        }
        .report-title-section h1 {
            font-size: 1.15rem;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .report-title-section p {
            font-size: 0.8rem;
            color: #475569;
            font-family: 'Courier Prime', monospace;
        }

        /* Report Section styling */
        .report-section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            background: #f1f5f9;
            padding: 6px 12px;
            margin-bottom: 12px;
            border-left: 4px solid var(--color-secondary);
            letter-spacing: 0.5px;
        }

        /* Grid data list */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .report-table th, .report-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 0.8rem;
            line-height: 1.4;
        }
        .report-table th {
            background-color: #f8fafc;
            font-weight: 600;
            text-align: left;
            width: 35%;
        }
        .report-table td {
            color: #0f172a;
        }

        /* Two columns details table */
        .grid-half {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Custom Decision Box */
        .decision-box {
            border: 2px solid #000;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            margin: 16px 0;
            background-color: #fafafa;
        }
        .decision-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .decision-value {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .decision-lunas {
            color: #0f766e; /* dark teal */
            border-color: #0f766e;
            background: #f0fdfa;
        }
        .decision-tidak-lunas {
            color: #be123c; /* dark red */
            border-color: #be123c;
            background: #fff1f2;
        }

        .decision-desc {
            font-size: 0.78rem;
            color: #475569;
            margin-top: 8px;
            line-height: 1.4;
        }

        /* Signatures block */
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
            width: 100%;
        }
        .signature-box {
            text-align: center;
            width: 40%;
            font-size: 0.8rem;
        }
        .signature-date {
            margin-bottom: 12px;
            text-align: right;
            font-size: 0.8rem;
            color: #000;
            width: 100%;
            padding-right: 20px;
        }
        .signature-role {
            margin-bottom: 70px;
            font-weight: 600;
        }
        .signature-name {
            font-weight: 700;
            text-decoration: underline;
        }
        .signature-nip {
            font-size: 0.72rem;
            color: #475569;
            margin-top: 2px;
        }

        /* Disclaimer footer on page */
        .report-footer {
            margin-top: auto; /* Push to bottom of page */
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            text-align: center;
            font-size: 0.68rem;
            color: #64748b;
            line-height: 1.4;
        }

        /* Styling optimized for print */
        @media print {
            .control-bar, .glow-bg {
                display: none !important;
            }
            body {
                background-color: #fff !important;
                color: #000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .report-paper {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-height: auto !important;
                background: #fff !important;
                color: #000 !important;
            }
        }
    </style>
</head>
<body>
    <div class="glow-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-card">
            <i data-lucide="alert-triangle" style="width: 48px; height: 48px; color: #ef4444; margin-bottom: 12px; display: inline-block;"></i>
            <h3>Terjadi Kesalahan</h3>
            <p style="color: var(--text-gray); margin-top: 6px;"><?php echo htmlspecialchars($error); ?></p>
            <a href="riwayat.php" class="btn-report btn-back" style="margin-top: 20px; display: inline-flex;">
                <i data-lucide="arrow-left"></i>
                <span>Kembali ke Riwayat</span>
            </a>
        </div>
    <?php else: 
        $formattedDate = date('d F Y', strtotime($row['created_at']));
        // Terjemahkan nama bulan ke Indonesia
        $months = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $formattedDateIndo = strtr($formattedDate, $months);
        $totalPendapatan = $row['pendapatan'] + $row['pendapatan_bersama'];
    ?>
        <!-- Control Header Panel -->
        <div class="control-bar">
            <div class="control-title">
                <i data-lucide="file-text" style="color: var(--color-primary); width:24px; height:24px;"></i>
                <div>
                    <h4>Cetak Laporan Prediksi</h4>
                    <p>ID Kredit: #<?php echo $row['Id_kredit']; ?> - Nasabah: <?php echo htmlspecialchars($row['Id_nasabah']); ?></p>
                </div>
            </div>
            <div class="control-buttons">
                <a href="riwayat.php" class="btn-report btn-back">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
                <button onclick="window.print()" class="btn-report btn-print">
                    <i data-lucide="printer"></i>
                    <span>Cetak Dokumen</span>
                </button>
                <button onclick="downloadPDF()" class="btn-report btn-pdf">
                    <i data-lucide="download"></i>
                    <span>Unduh PDF</span>
                </button>
            </div>
        </div>

        <!-- The Printable Document Paper -->
        <div id="report-paper-container" class="report-paper">
            <!-- Kop Surat -->
            <div class="kop-surat">
                <img src="assets/logo-bni.jpg" alt="Logo BNI" class="kop-logo">
                <div class="kop-text">
                    <h2>PT Bank Negara Indonesia (Persero) Tbk</h2>
                    <h3>Kantor Cabang Tarandam Padang</h3>
                    <p>Jl. Sisingamangaraja No. 34, Tarandam, Ganting Parak Gadang, Kec. Padang Timur, Kota Padang</p>
                    <p>Telepon: (0751) 31553, 31554 | Website: www.bni.co.id</p>
                </div>
            </div>

            <!-- Judul Dokumen -->
            <div class="report-title-section">
                <h1>LAPORAN ANALISIS KELAYAKAN DEBITUR</h1>
                <p>Nomor Dokumen: BNI-TRD/SPK-KNN/<?php echo date('Y/m', strtotime($row['created_at'])); ?>/<?php echo sprintf('%04d', $row['Id_kredit']); ?></p>
            </div>

            <!-- Seksi 1: Profil Nasabah -->
            <div class="report-section">
                <div class="section-title">I. Profil Identitas Nasabah</div>
                <table class="report-table">
                    <tr>
                        <th>ID Nasabah</th>
                        <td><strong><?php echo htmlspecialchars($row['Id_nasabah']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Jenis Kelamin</th>
                        <td><?php echo htmlspecialchars($row['Jenis_Kelamin']); ?></td>
                    </tr>
                    <tr>
                        <th>Pendidikan Terakhir</th>
                        <td><?php echo htmlspecialchars($row['Edukasi']); ?></td>
                    </tr>
                    <tr>
                        <th>Status Pernikahan</th>
                        <td><?php echo htmlspecialchars($row['Status_Pernikahan'] === 'Ya' ? 'Sudah Menikah' : 'Belum Menikah'); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Seksi 2: Atribut Finansial & Pengajuan -->
            <div class="report-section">
                <div class="section-title">II. Atribut Finansial & Pengajuan Kredit</div>
                <div class="grid-half">
                    <table class="report-table">
                        <tr>
                            <th>Status Pekerjaan</th>
                            <td><?php echo htmlspecialchars($row['status_pekerjaan']); ?></td>
                        </tr>
                        <tr>
                            <th>Jumlah Tanggungan</th>
                            <td><?php echo $row['Tangguan']; ?> Orang</td>
                        </tr>
                        <tr>
                            <th>Pendapatan Utama</th>
                            <td>Rp <?php echo number_format($row['pendapatan'], 0, ',', '.'); ?> /bulan</td>
                        </tr>
                        <tr>
                            <th>Pendapatan Bersama</th>
                            <td>Rp <?php echo number_format($row['pendapatan_bersama'], 0, ',', '.'); ?> /bulan</td>
                        </tr>
                        <tr>
                            <th>Total Pendapatan</th>
                            <td><strong>Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?> /bulan</strong></td>
                        </tr>
                    </table>
                    <table class="report-table">
                        <tr>
                            <th>Jumlah Pinjaman</th>
                            <td><strong>Rp <?php echo number_format($row['Jumlah_pinjaman'], 0, ',', '.'); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Jangka Waktu (Tenor)</th>
                            <td><?php echo $row['jangka_waktu']; ?> Bulan</td>
                        </tr>
                        <tr>
                            <th>Kepemilikan Aset</th>
                            <td><?php echo htmlspecialchars($row['Asset']); ?></td>
                        </tr>
                        <tr>
                            <th>Riwayat Kredit Terakhir</th>
                            <td>
                                <?php echo $row['riwayat_kredit'] === 1 ? 'Baik (Lancar/Tidak Ada Tunggakan)' : 'Buruk (Pernah Macet/Ada Tunggakan)'; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Seksi 3: Hasil Analisis KNN -->
            <div class="report-section">
                <div class="section-title">III. Analisis Keputusan Kelayakan Kredit (Algoritma KNN)</div>
                <p style="font-size: 0.8rem; color: #334155; margin-bottom: 12px; text-align: justify; line-height: 1.4;">
                    Analisis kelayakan kredit ini diproses menggunakan metode <strong>K-Nearest Neighbor (KNN)</strong> dengan membandingkan parameter atribut keuangan nasabah di atas terhadap riwayat data latih (dataset historis) di Bank BNI Tarandam Padang.
                </p>
                <table class="report-table">
                    <tr>
                        <th style="width: 50%;">Parameter Tetangga Terdekat (Nilai K)</th>
                        <td>K = <?php echo $row['k_value']; ?></td>
                    </tr>
                    <tr>
                        <th>Distribusi Suara (Voting Tetangga)</th>
                        <td>
                            Model membandingkan K tetangga terdekat dengan hasil:
                            <br>• Lancar: <strong><?php echo $row['hasil_prediksi'] === 'Lancar' ? 'Mayoritas' : 'Minoritas'; ?></strong>
                            <br>• Macet: <strong><?php echo $row['hasil_prediksi'] === 'Macet' ? 'Mayoritas' : 'Minoritas'; ?></strong>
                        </td>
                    </tr>
                </table>

                <!-- Verdict Box -->
                <?php if ($row['hasil_prediksi'] === 'Lancar'): ?>
                    <div class="decision-box decision-lunas">
                        <div class="decision-title">Rekomendasi Keputusan Akhir</div>
                        <div class="decision-value">KREDIT LAYAK DISETUJUI (LANCAR)</div>
                        <div class="decision-desc">
                            Berdasarkan klasifikasi algoritma KNN, nasabah dinilai memiliki profil finansial dan karakter kredit yang menyerupai debitur lancar terdahulu. Risiko terjadinya kredit macet tergolong <strong>Rendah</strong>.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="decision-box decision-tidak-lunas">
                        <div class="decision-title">Rekomendasi Keputusan Akhir</div>
                        <div class="decision-value">KREDIT BERISIKO TINGGI (MACET)</div>
                        <div class="decision-desc">
                            Berdasarkan klasifikasi algoritma KNN, nasabah dinilai memiliki profil keuangan atau riwayat yang memiliki kedekatan dengan kasus kredit bermasalah terdahulu. Risiko terjadinya kredit macet tergolong <strong>Tinggi</strong>.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tanda Tangan -->
            <div class="signature-date">
                Padang, <?php echo $formattedDateIndo; ?>
            </div>
            <div class="signature-container">
                <div class="signature-box">
                    <div class="signature-role">Analis / Petugas Kredit</div>
                    <div class="signature-name"><?php echo htmlspecialchars($_SESSION['user']['nama']); ?></div>
                    <div class="signature-nip">NIP. <?php echo sprintf('%08d', 10203000 + $_SESSION['user']['id']); ?></div>
                </div>
                <div class="signature-box">
                    <div class="signature-role">Mengetahui / Pimpinan Cabang</div>
                    <div class="signature-name">Hendra Gunawan, S.E.</div>
                    <div class="signature-nip">NIP. 19820412 200812 1 002</div>
                </div>
            </div>

            <!-- Footer Disclaimer -->
            <div class="report-footer">
                * Laporan ini diterbitkan secara otomatis oleh Sistem Pendukung Keputusan Kelayakan Kredit Metode K-Nearest Neighbor Bank BNI Cabang Tarandam Padang.<br>
                Keputusan akhir pemberian fasilitas kredit tetap berada pada wewenang komite kredit Kantor Cabang sesuai dengan regulasi yang berlaku.
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Inisialisasi ikon Lucide
        lucide.createIcons();

        // Fungsi unduh PDF menggunakan html2pdf.js
        function downloadPDF() {
            const element = document.getElementById('report-paper-container');
            const idNasabah = "<?php echo $row ? htmlspecialchars($row['Id_nasabah']) : 'Laporan'; ?>";
            const idKredit = "<?php echo $row ? $row['Id_kredit'] : '0'; ?>";
            
            // Konfigurasi konversi PDF
            const opt = {
                margin:       10,
                filename:     'Laporan_Analisis_Kredit_' + idNasabah + '_#' + idKredit + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Jalankan html2pdf
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
