<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/koneksi.php';

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$idSiswa = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idSiswa <= 0) {
    header('Location: students-recap.php');
    exit;
}

$pesanError = '';
$pesanSukses = '';

// 1. Ambil data lama dari database untuk ditampilkan di form
try {
    $stmt = $pdo->prepare('SELECT id, child_name, age, school_class, phone_number, photo_path FROM students WHERE id = ?');
    $stmt->execute([$idSiswa]);
    $siswa = $stmt->fetch();

    if (!$siswa) {
        die('Waduh, data anak tidak ditemukan!');
    }
} catch (Throwable $e) {
    die('Gagal memuat data: ' . $e->getMessage());
}

// 2. Proses jika form dikirim (Tombol Simpan ditekan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = trim((string)($_POST['child_name'] ?? ''));
    $umur = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    $kelas = trim((string)($_POST['school_class'] ?? ''));
    $noHp = trim((string)($_POST['phone_number'] ?? ''));
    $fotoBaru = $siswa['photo_path']; // Default pakai foto lama

    if ($namaLengkap === '') {
        $pesanError = 'Nama lengkap anak tidak boleh kosong ya! 🚫';
    } else {
        try {
            // Cek apakah ada file foto baru yang di-upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $infoFile = pathinfo($_FILES['photo']['name']);
                $ekstensi = strtolower($infoFile['extension'] ?? '');
                $ekstensiDiijinkan = ['jpg', 'jpeg', 'png'];

                if (in_array($ekstensi, $ekstensiDiijinkan, true)) {
                    // Buat nama acak unik agar tidak bentrok
                    $namaFileBaru = bin2hex(random_bytes(8)) . '.' . $ekstensi;
                    $targetFolder = __DIR__ . '/uploads/' . $namaFileBaru;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFolder)) {
                        // Hapus foto lama dari server jika ada untuk menghemat memori penyimpanan
                        if (!empty($siswa['photo_path']) && file_exists(__DIR__ . '/uploads/' . $siswa['photo_path'])) {
                            @unlink(__DIR__ . '/uploads/' . $siswa['photo_path']);
                        }
                        $fotoBaru = $namaFileBaru;
                    }
                } else {
                    $pesanError = 'Format gambar harus JPG, JPEG, atau PNG! 📸';
                }
            }

            // Jika tidak ada error file, jalankan UPDATE ke database
            if ($pesanError === '') {
                $updateStmt = $pdo->prepare('UPDATE students SET child_name = ?, age = ?, school_class = ?, phone_number = ?, photo_path = ? WHERE id = ?');
                $updateStmt->execute([$namaLengkap, $umur, $kelas, $noHp, $fotoBaru, $idSiswa]);
                
                $pesanSukses = 'Hore! Data profil berhasil diperbarui ✨';
                
                // Refresh data lokal supaya tampilan form ikut ter-update
                $siswa['child_name'] = $namaLengkap;
                $siswa['age'] = $umur;
                $siswa['school_class'] = $kelas;
                $siswa['phone_number'] = $noHp;
                $siswa['photo_path'] = $fotoBaru;
            }
        } catch (Throwable $e) {
            $pesanError = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Data Anak - GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #1e293b;
            font-family: 'Nunito', sans-serif;
        }
        h1, h2, .brand-title {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
        }
        .navbar-fun {
            background-color: #ffffff;
            border-bottom: 4px solid #1e293b;
            box-shadow: 0 4px 0px #1e293b;
        }
        .bubble-card {
            background: #ffffff;
            border: 3px solid #1e293b;
            border-radius: 24px;
            box-shadow: 5px 5px 0px #1e293b;
        }
        .input-fun {
            border: 3px solid #1e293b;
            border-radius: 12px;
            padding: 10px;
            font-weight: bold;
            box-shadow: inset 2px 2px 0px rgba(0,0,0,0.1);
        }
        .input-fun:focus {
            border-color: #3a86ff;
            box-shadow: 3px 3px 0px #1e293b;
        }
        .btn-header-nav {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            border: 3px solid #1e293b;
            border-radius: 14px;
            padding: 10px 20px;
            box-shadow: 4px 4px 0px #1e293b;
            background-color: #ffbe0b;
            color: #1e293b !important;
            text-decoration: none;
        }
        .btn-simpan {
            font-family: 'Fredoka', sans-serif;
            background-color: #ff006e;
            color: white;
            border: 3px solid #1e293b;
            border-radius: 14px;
            padding: 12px;
            box-shadow: 4px 4px 0px #1e293b;
            font-weight: bold;
            width: 100%;
        }
        .btn-simpan:hover {
            background-color: #e0005a;
            color: white;
            box-shadow: 5px 5px 0px #1e293b;
        }
        .avatar-preview {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 20px;
            border: 3px solid #1e293b;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-fun py-3 mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <img src="Logo GSM.PNG" alt="Logo GSM" class="img-fluid" style="height: 45px; object-fit: contain;">
            <div class="d-flex flex-column">
                <span class="fs-4 text-dark brand-title m-0">GSMConnect</span>
                <small class="text-muted fw-bold" style="font-size: 0.8rem;">Formulir Perbarui Data</small>
            </div>
        </div>
        <a href="students-recap.php" class="btn btn-header-nav">🔙 Kembali</a>
    </div>
</nav>

<main class="container mb-5" style="max-width: 600px;">
    <div class="bubble-card p-4 bg-white">
        <h2 class="text-center mb-4 text-dark">✏️ Perbarui Profil Anak</h2>

        <?php if ($pesanError !== ''): ?>
            <div class="alert alert-danger fw-bold border border-3 border-dark rounded-3" style="box-shadow: 3px 3px 0px #000;"><?= $pesanError ?></div>
        <?php endif; ?>

        <?php if ($pesanSukses !== ''): ?>
            <div class="alert alert-success fw-bold border border-3 border-dark rounded-3" style="box-shadow: 3px 3px 0px #000;"><?= $pesanSukses ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Nama Lengkap Anak *</label>
                <input type="text" name="child_name" class="form-control input-fun" value="<?= proteksi((string)$siswa['child_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Umur (Tahun)</label>
                <input type="number" name="age" class="form-control input-fun" value="<?= (int)$siswa['age'] ?>" min="0">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Kelas Sekolah</label>
                <input type="text" name="school_class" class="form-control input-fun" value="<?= proteksi((string)$siswa['school_class']) ?>" placeholder="Contoh: 3 SD / TK B">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-dark">No. HP Orang Tua</label>
                <input type="text" name="phone_number" class="form-control input-fun" value="<?= proteksi((string)$siswa['phone_number']) ?>">
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-dark">Foto Profil Saat Ini</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <?php if (!empty($siswa['photo_path']) && file_exists(__DIR__ . '/uploads/' . $siswa['photo_path'])): ?>
                        <img src="uploads/<?= proteksi($siswa['photo_path']) ?>" alt="Foto" class="avatar-preview">
                    <?php else: ?>
                        <div class="avatar-preview d-flex align-items-center justify-content-center bg-warning fs-3">🧸</div>
                    <?php endif; ?>
                    <div>
                        <small class="text-muted d-block mb-1">Pilih file baru jika ingin mengganti foto:</small>
                        <input type="file" name="photo" class="form-control input-fun fs-6">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-simpan">💾 Simpan Perubahan Data</button>
        </form>
    </div>
</main>

</body>
</html>