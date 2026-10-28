<?php
// db.php
// Konfigurasi Database MySQL

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_skripsi');

function getDB() {
    try {
        // Hubungkan ke server MySQL terlebih dahulu untuk memastikan database target ada
        $dsnWithoutDb = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $dbTmp = new PDO($dsnWithoutDb, DB_USER, DB_PASS);
        $dbTmp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Buat database jika belum ada
        $dbTmp->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $dbTmp = null; // Tutup koneksi sementara
        
        // Hubungkan ke database target
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $db = new PDO($dsn, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Buat tabel jika belum ada
        initializeTables($db);
        
        return $db;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

function initializeTables($db) {
    // Cek migrasi: jika tabel nasabah ada tapi tidak punya kolom Status_Pernikahan, reset DB.
    $tableCheck = $db->query("SHOW TABLES LIKE 'nasabah'")->fetch();
    if ($tableCheck) {
        try {
            $db->query("SELECT Status_Pernikahan FROM nasabah LIMIT 1");
        } catch (PDOException $e) {
            // Kolom tidak ada, drop tables agar dibuat ulang dengan struktur baru
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("DROP TABLE IF EXISTS riwayat");
            $db->exec("DROP TABLE IF EXISTS dataset");
            $db->exec("DROP TABLE IF EXISTS nasabah");
            $db->exec("DROP TABLE IF EXISTS users");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    }

    // Cek migrasi: jika tabel users ada tapi tidak punya kolom role, reset DB.
    $userCheck = $db->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($userCheck) {
        try {
            $db->query("SELECT role FROM users LIMIT 1");
        } catch (PDOException $e) {
            // Kolom tidak ada, drop tables agar dibuat ulang dengan struktur baru
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("DROP TABLE IF EXISTS riwayat");
            $db->exec("DROP TABLE IF EXISTS dataset");
            $db->exec("DROP TABLE IF EXISTS nasabah");
            $db->exec("DROP TABLE IF EXISTS users");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    }

    // 1. Tabel users
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        nama VARCHAR(100),
        role VARCHAR(20) DEFAULT 'petugas',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // 2. Tabel nasabah
    $db->exec("CREATE TABLE IF NOT EXISTS nasabah (
        Id_Nasabah VARCHAR(50) PRIMARY KEY,
        Jenis_Kelamin VARCHAR(20),
        Edukasi VARCHAR(50),
        Status_Pernikahan VARCHAR(10)
    ) ENGINE=InnoDB");

    // 3. Tabel dataset (Data Latih)
    $db->exec("CREATE TABLE IF NOT EXISTS dataset (
        Id_kredit INT AUTO_INCREMENT PRIMARY KEY,
        Id_nasabah VARCHAR(50),
        Jenis_Kelamin VARCHAR(20),
        Edukasi VARCHAR(50),
        Status_Pernikahan VARCHAR(10),
        Tangguan INT,
        pendapatan DOUBLE,
        pendapatan_bersama DOUBLE,
        Jumlah_pinjaman DOUBLE,
        jangka_waktu INT,
        riwayat_kredit INT,
        status_pekerjaan VARCHAR(50),
        Asset VARCHAR(50),
        label_aktual VARCHAR(20),
        FOREIGN KEY (Id_nasabah) REFERENCES nasabah(Id_Nasabah) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // 4. Tabel riwayat (Log Prediksi)
    $db->exec("CREATE TABLE IF NOT EXISTS riwayat (
        Id_kredit INT AUTO_INCREMENT PRIMARY KEY,
        Id_nasabah VARCHAR(50),
        Jenis_Kelamin VARCHAR(20),
        Edukasi VARCHAR(50),
        Status_Pernikahan VARCHAR(10),
        Tangguan INT,
        pendapatan DOUBLE,
        pendapatan_bersama DOUBLE,
        Jumlah_pinjaman DOUBLE,
        jangka_waktu INT,
        riwayat_kredit INT,
        status_pekerjaan VARCHAR(50),
        Asset VARCHAR(50),
        k_value INT,
        hasil_prediksi VARCHAR(20),
        label_aktual VARCHAR(20) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (Id_nasabah) REFERENCES nasabah(Id_Nasabah) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Cek jika tabel users kosong, lakukan seeding user admin default
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        seedDefaultUser($db);
        seedDefaultData($db);
    }

    // Auto-migrate existing values of Lunas/Tidak Lunas to Lancar/Macet
    try {
        $db->exec("UPDATE dataset SET label_aktual = 'Lancar' WHERE label_aktual = 'Lunas'");
        $db->exec("UPDATE dataset SET label_aktual = 'Macet' WHERE label_aktual = 'Tidak Lunas'");
        $db->exec("UPDATE riwayat SET hasil_prediksi = 'Lancar' WHERE hasil_prediksi = 'Lunas'");
        $db->exec("UPDATE riwayat SET hasil_prediksi = 'Macet' WHERE hasil_prediksi = 'Tidak Lunas'");
        $db->exec("UPDATE riwayat SET label_aktual = 'Lancar' WHERE label_aktual = 'Lunas'");
        $db->exec("UPDATE riwayat SET label_aktual = 'Macet' WHERE label_aktual = 'Tidak Lunas'");
    } catch (PDOException $e) {
        // Ignore if any errors occur during migration
    }
}

function seedDefaultUser($db) {
    $username = 'admin';
    $password = password_hash('admin', PASSWORD_BCRYPT);
    $nama = 'Administrator';
    $role = 'admin';
    
    $stmt = $db->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password, $nama, $role]);

    // Seeding default petugas
    $usernamePetugas = 'petugas';
    $passwordPetugas = password_hash('petugas', PASSWORD_BCRYPT);
    $namaPetugas = 'Petugas Analisis';
    $rolePetugas = 'petugas';
    $stmt->execute([$usernamePetugas, $passwordPetugas, $namaPetugas, $rolePetugas]);
}

function seedDefaultData($db) {
    // Array Data Master Nasabah
    $nasabahSeed = [
        ['NSB001', 'Laki-laki', 'Lulus', 'Ya'],
        ['NSB002', 'Perempuan', 'Tidak Lulus', 'Ya'],
        ['NSB003', 'Laki-laki', 'Lulus', 'Ya'],
        ['NSB004', 'Laki-laki', 'Lulus', 'Tidak'],
        ['NSB005', 'Perempuan', 'Lulus', 'Tidak'],
        ['NSB006', 'Laki-laki', 'Tidak Lulus', 'Ya'],
        ['NSB007', 'Perempuan', 'Lulus', 'Ya'],
        ['NSB008', 'Laki-laki', 'Lulus', 'Ya'],
        ['NSB009', 'Perempuan', 'Tidak Lulus', 'Tidak'],
        ['NSB010', 'Laki-laki', 'Lulus', 'Ya'],
        ['NSB011', 'Perempuan', 'Lulus', 'Ya'],
        ['NSB012', 'Laki-laki', 'Tidak Lulus', 'Tidak'],
        ['NSB013', 'Laki-laki', 'Lulus', 'Ya'],
        ['NSB014', 'Perempuan', 'Lulus', 'Ya'],
        ['NSB015', 'Laki-laki', 'Lulus', 'Ya'],
        ['NSB016', 'Perempuan', 'Tidak Lulus', 'Tidak'],
        ['NSB017', 'Laki-laki', 'Lulus', 'Tidak'],
        ['NSB018', 'Perempuan', 'Lulus', 'Ya'],
        ['NSB019', 'Laki-laki', 'Lulus', 'Ya'],
        ['NSB020', 'Perempuan', 'Tidak Lulus', 'Ya'],
    ];

    // Array Data Latih (Dataset)
    $datasetSeed = [
        ['NSB001', 'Laki-laki', 'Lulus', 'Ya', 0, 6000000, 0, 25000000, 12, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB002', 'Perempuan', 'Tidak Lulus', 'Ya', 2, 2500000, 1500000, 40000000, 36, 0, 'Tidak Bekerja', 'Tidak Memiliki Aset', 'Macet'],
        ['NSB003', 'Laki-laki', 'Lulus', 'Ya', 1, 8500000, 4000000, 80000000, 24, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB004', 'Laki-laki', 'Lulus', 'Tidak', 3, 4000000, 0, 50000000, 24, 0, 'Bekerja', 'Tidak Memiliki Aset', 'Macet'],
        ['NSB005', 'Perempuan', 'Lulus', 'Tidak', 0, 12000000, 0, 120000000, 60, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB006', 'Laki-laki', 'Tidak Lulus', 'Ya', 1, 3000000, 0, 15000000, 12, 1, 'Bekerja', 'Tidak Memiliki Aset', 'Lancar'],
        ['NSB007', 'Perempuan', 'Lulus', 'Ya', 2, 5000000, 3000000, 60000000, 24, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB008', 'Laki-laki', 'Lulus', 'Ya', 1, 4500000, 2000000, 45000000, 18, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB009', 'Perempuan', 'Tidak Lulus', 'Tidak', 3, 2000000, 0, 30000000, 36, 0, 'Tidak Bekerja', 'Tidak Memiliki Aset', 'Macet'],
        ['NSB010', 'Laki-laki', 'Lulus', 'Ya', 2, 7000000, 0, 75000000, 24, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB011', 'Perempuan', 'Lulus', 'Ya', 1, 9000000, 5000000, 100000000, 36, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB012', 'Laki-laki', 'Tidak Lulus', 'Tidak', 0, 3500000, 0, 20000000, 12, 0, 'Bekerja', 'Tidak Memiliki Aset', 'Macet'],
        ['NSB013', 'Laki-laki', 'Lulus', 'Ya', 2, 5500000, 0, 50000000, 18, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB014', 'Perempuan', 'Lulus', 'Ya', 1, 6000000, 3500000, 70000000, 24, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB015', 'Laki-laki', 'Lulus', 'Ya', 4, 15000000, 0, 150000000, 48, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB016', 'Perempuan', 'Tidak Lulus', 'Tidak', 2, 2800000, 0, 25000000, 24, 0, 'Tidak Bekerja', 'Tidak Memiliki Aset', 'Macet'],
        ['NSB017', 'Laki-laki', 'Lulus', 'Tidak', 0, 5000000, 0, 30000000, 12, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB018', 'Perempuan', 'Lulus', 'Ya', 1, 4800000, 2500000, 40000000, 18, 1, 'Bekerja', 'Standar', 'Lancar'],
        ['NSB019', 'Laki-laki', 'Lulus', 'Ya', 3, 6500000, 0, 80000000, 36, 0, 'Bekerja', 'Tidak Memiliki Aset', 'Macet'],
        ['NSB020', 'Perempuan', 'Tidak Lulus', 'Ya', 2, 2200000, 1800000, 35000000, 24, 1, 'Tidak Bekerja', 'Tidak Memiliki Aset', 'Macet'],
    ];

    // Seed Nasabah
    $stmtNsb = $db->prepare("INSERT INTO nasabah (Id_Nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan) VALUES (?, ?, ?, ?)");
    foreach ($nasabahSeed as $nsb) {
        $stmtNsb->execute($nsb);
    }

    // Seed Dataset
    $stmtDst = $db->prepare("INSERT INTO dataset (Id_nasabah, Jenis_Kelamin, Edukasi, Status_Pernikahan, Tangguan, pendapatan, pendapatan_bersama, Jumlah_pinjaman, jangka_waktu, riwayat_kredit, status_pekerjaan, Asset, label_aktual) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($datasetSeed as $dst) {
        $stmtDst->execute($dst);
    }
}

function resetDatabase($db) {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("DROP TABLE IF EXISTS riwayat");
    $db->exec("DROP TABLE IF EXISTS dataset");
    $db->exec("DROP TABLE IF EXISTS nasabah");
    $db->exec("DROP TABLE IF EXISTS users");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    initializeTables($db);
}
?>
