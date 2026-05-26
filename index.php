<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/koneksi.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notifikasi = ['tipe' => null, 'pesan' => null];

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (isset($_GET['teaching_date'])) {
    $_SESSION['active_teaching_date'] = (string)$_GET['teaching_date'];
} elseif (empty($_SESSION['active_teaching_date'])) {
    $_SESSION['active_teaching_date'] = date('Y-m-d');
}

$tanggalSesiAktif = $_SESSION['active_teaching_date'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenForm = (string)($_POST['csrf_token'] ?? '');
    $jenisForm = (string)($_POST['form_type'] ?? '');
    
    if (!hash_equals($_SESSION['csrf_token'], $tokenForm)) {
        $notifikasi = ['tipe' => 'danger', 'pesan' => 'Waktu Sesi habis, yuk muat ulang halaman!'];
    } else {
        $kendala = [];

        if ($jenisForm === 'register_child') {
            $namaAnak = trim((string)($_POST['child_name'] ?? ''));
            $umurAnak = (int)($_POST['age'] ?? 0);
            $kelasAnak = trim((string)($_POST['school_class'] ?? ''));
            $noHp = trim((string)($_POST['phone_number'] ?? ''));

            if ($namaAnak === '') $kendala[] = 'Nama Anak jangan sampai kosong!';
            if ($umurAnak <= 0) $kendala[] = 'Umur anak harus diisi dengan angka yang benar!';
            if ($kelasAnak === '') $kendala[] = 'Kelas sekolah anak mohon diisi!';

            $namaFileFoto = null;
            if (count($kendala) === 0 && isset($_FILES['child_photo']) && $_FILES['child_photo']['error'] === UPLOAD_ERR_OK) {
                $ekstensi = strtolower(pathinfo($_FILES['child_photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $namaFileFoto = bin2hex(random_bytes(16)) . '.' . $ekstensi;
                    if (!is_dir(__DIR__ . '/uploads')) {
                        mkdir(__DIR__ . '/uploads', 0777, true);
                    }
                    move_uploaded_file($_FILES['child_photo']['tmp_name'], __DIR__ . '/uploads/' . $namaFileFoto);
                } else {
                    $kendala[] = 'Format foto wajib JPG, PNG, atau WEBP!';
                }
            }

            if (count($kendala) === 0) {
                $kirimData = $pdo->prepare('INSERT INTO students (child_name, age, school_class, phone_number, photo_path) VALUES (?, ?, ?, ?, ?)');
                $kirimData->execute([$namaAnak, $umurAnak, $kelasAnak, $noHp, $namaFileFoto]);
                header('Location: index.php?sukses=anak_baru');
                exit;
            }
        }
        
        elseif ($jenisForm === 'mark_attendance') {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $statusHadir = (string)($_POST['attendance_status'] ?? '0');

            if ($studentId <= 0) {
                $kendala[] = 'Silakan pilih nama anak didik terlebih dahulu ya!';
            }

            if (count($kendala) === 0) {
                $jsonHasil = json_encode(['attendance' => ($statusHadir === '1'), 'stationery'=>true, 'homework'=>true, 'behavior_good'=>true]);
                $kirimPresensi = $pdo->prepare('INSERT INTO attendance_records (student_id, session_date, checklist_json) VALUES (?, ?, ?)');
                $kirimPresensi->execute([$studentId, $tanggalSesiAktif, $jsonHasil]);
                header('Location: index.php?sukses=absen_oke');
                exit;
            }
        }

        if (count($kendala) > 0) {
            $notifikasi = ['tipe' => 'warning', 'pesan' => implode(' ', $kendala)];
        }
    }
}

if (($_GET['sukses'] ?? '') === 'anak_baru') {
    $notifikasi = ['tipe' => 'success', 'pesan' => 'Hore! Data Anak baru berhasil disimpan! 🎉'];
} elseif (($_GET['sukses'] ?? '') === 'absen_oke') {
    $notifikasi = ['tipe' => 'success', 'pesan' => 'Presensi harian berhasil dicatat! Mantap! 👍'];
}

$totalAnakAktif = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$dataSiswa = $pdo->query('SELECT id, child_name FROM students ORDER BY child_name ASC')->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GSMConnect - Sistem Data Kehadiran Anak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f3fbf7;
            color: #1e293b;
            font-family: 'Nunito', sans-serif;
        }
        h1, h2, h3, h4, .fun-title {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
        }
        .navbar-fun {
            background-color: #ffffff;
            border-bottom: 4px solid #1e293b;
            box-shadow: 0 4px 0px #1e293b;
        }
        .logo-gsm-box {
            background-color: #ff006e;
            color: white;
            padding: 6px 14px;
            border: 3px solid #1e293b;
            border-radius: 12px;
            box-shadow: 2px 2px 0px #1e293b;
            font-weight: 900;
            font-family: 'Fredoka', sans-serif;
            letter-spacing: 1px;
        }
        .bubble-card {
            background: #ffffff;
            border: 3px solid #1e293b;
            border-radius: 24px;
            box-shadow: 5px 5px 0px #1e293b;
        }
        
        /* === TOMBOL HEADER RESPONSIVE LENGKAP === */
        .btn-header-nav {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            border: 3px solid #1e293b;
            border-radius: 14px;
            padding: 10px 16px;
            box-shadow: 4px 4px 0px #1e293b;
            transition: all 0.15s ease-in-out;
            text-transform: uppercase;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-header-nav:hover {
            transform: translate(-2px, -2px);
            box-shadow: 5px 5px 0px #1e293b;
        }
        .btn-nav-blue { background-color: #3a86ff; color: white !important; }
        .btn-nav-yellow { background-color: #ffbe0b; color: #1e293b !important; }
        .btn-nav-purple { background-color: #9b5de5; color: white !important; }

        .btn-submit-fun {
            background-color: #06d6a0;
            color: white;
            font-weight: 900;
            font-family: 'Fredoka', sans-serif;
            border: 3px solid #1e293b;
            box-shadow: 4px 4px 0px #1e293b;
            border-radius: 14px;
        }
        .btn-submit-fun:hover {
            background-color: #05b88a;
            transform: translate(1px, 1px);
            box-shadow: 3px 3px 0px #1e293b;
        }
        .form-control, .form-select {
            border: 3px solid #1e293b !important;
            border-radius: 12px;
            font-weight: 700;
            padding: 10px;
        }
        
        .nav-tabs-fun {
            display: flex;
            flex-wrap: nowrap; 
            gap: 5px;
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch;
        }
        .nav-tabs-fun .nav-item {
            flex: 1 1 auto;
            text-align: center;
        }
        .nav-tabs-fun .nav-link {
            border: 3px solid #1e293b !important;
            border-radius: 14px 14px 14px 14px;
            font-weight: 900;
            background: #e2e8f0;
            color: #1e293b;
            padding: 10px 12px;
            font-size: 0.8rem; 
            width: 100%;
            white-space: nowrap;
        }
        .nav-tabs-fun .nav-link.active {
            background: #ffffff !important;
            color: #ff006e !important;
            box-shadow: 2px 2px 0px #1e293b;
        }

        @media (min-width: 768px) {
            .btn-header-nav {
                font-size: 0.95rem;
                padding: 10px 20px;
            }
            .nav-tabs-fun .nav-link {
                font-size: 0.95rem;
                padding: 12px 24px;
                border-radius: 14px 14px 0 0;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-fun py-3 mb-4 mb-md-5">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
       <a class="navbar-brand d-flex align-items-center gap-2 gap-md-3 text-decoration-none" href="index.php">
            <img src="Logo GSM.PNG" alt="Logo GSM.PNG" class="img-fluid" style="height: 45px; object-fit: contain;">
            
            <div class="d-flex flex-column text-start">
                <span class="fs-4 fs-md-3 text-dark fun-title m-0">GSMConnect</span>
                <small class="text-muted fw-bold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Gerakan Sahabat Mengajar IT-PLN</small>
            </div>
       </a>
        
        <div class="d-flex flex-wrap w-100 w-md-auto gap-2 gap-md-3 justify-content-center">
            <a href="students-recap.php" class="btn btn-header-nav btn-nav-blue flex-fill flex-md-grow-0">
                👶 Profil Anak Aktif
            </a>
            <a href="recap.php" class="btn btn-header-nav btn-nav-yellow flex-fill flex-md-grow-0">
                📅 Riwayat Sesi Pengajaran
            </a>
            <a href="proker.php" class="btn btn-header-nav btn-nav-purple flex-fill flex-md-grow-0" style="background-color: #9b5de5; color: white !important;">
                📋 Agenda Proker
            </a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <div class="row g-3 g-md-4 mb-4 mb-md-5">
        <div class="col-12 col-md-4">
            <a href="students-recap.php" class="text-decoration-none text-dark">
                <div class="bubble-card p-3 p-md-4 text-center h-100" style="background-color: #ffc6ff;">
                    <h6 class="text-uppercase fw-black mb-1" style="letter-spacing:1px; font-size: 0.85rem;">Data Anak Anak Aktif</h6>
                    <div class="display-4 fw-black" style="font-family:'Fredoka';"><?= $totalAnakAktif ?></div>
                    <span class="badge bg-dark px-2 py-1 rounded-pill mt-1" style="font-size:0.75rem;">Lihat Detail Anak 🔍</span>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-8">
            <div class="bubble-card p-3 p-md-4 h-100" style="background-color: #caffbf;">
                <form method="get" class="row align-items-center h-100 g-2">
                    <div class="col-12 col-sm-7">
                        <h5 class="mb-1">Pilih Tanggal Pengajaran 📅</h5>
                        <p class="mb-0 text-muted d-none d-sm-block" style="font-size: 0.85rem;">Tanggal terpilih mengunci otomatis seluruh inputan sesi pengajaran.</p>
                    </div>
                    <div class="col-12 col-sm-5">
                        <input type="date" class="form-control text-center fs-6 py-2" name="teaching_date" value="<?= proteksi($tanggalSesiAktif) ?>" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <?php if ($notifikasi['pesan'] !== null): ?>
                <div class="alert alert-dark border-3 border-dark fw-bold mb-3" style="font-size: 0.9rem;" role="alert">
                    📢 <?= proteksi($notifikasi['pesan']) ?>
                </div>
            <?php endif; ?>

            <div class="bubble-card p-3 p-md-4">
                <ul class="nav nav-tabs nav-tabs-fun border-0 mb-4" id="funTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="absen-tab" data-bs-toggle="tab" data-bs-target="#absen-panel" type="button">📝 ISI PRESENSI PENGAJARAN</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tambah-tab" data-bs-toggle="tab" data-bs-target="#tambah-panel" type="button">✨ DAFTAR ANAK</button>
                    </li>
                </ul>

                <div class="tab-content bg-white p-1">
                    <div class="tab-pane fade show active" id="absen-panel" role="tabpanel">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= proteksi((string)$_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="form_type" value="mark_attendance">

                            <div class="col-12">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.9rem;">Pilih Anak Yang Hadir:</label>
                                <select class="form-select fs-6" name="student_id" required>
                                    <option value="" disabled selected>-- Pilih Siapa Yang Datang --</option>
                                    <?php foreach ($dataSiswa as $siswa): ?>
                                        <option value="<?= (int)$siswa['id'] ?>"><?= proteksi($siswa['child_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 mt-2">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.9rem;">Status Kehadiran:</label>
                                <select class="form-select fs-6" name="attendance_status">
                                    <option value="1" selected>🟢 HADIR & SEMANGAT BELAJAR!</option>
                                    <option value="0">🔴 HALANGAN / IZIN</option>
                                </select>
                            </div>

                            <div class="col-12 text-center pt-3">
                                <button type="submit" class="btn btn-submit-fun w-100 py-2.5 fs-6">💾 Simpan Presensi Anak</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="tambah-panel" role="tabpanel">
                        <form method="post" enctype="multipart/form-data" class="row g-2 g-md-3">
                            <input type="hidden" name="csrf_token" value="<?= proteksi((string)$_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="form_type" value="register_child">

                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-size: 0.9rem;">Nama Lengkap Anak:</label>
                                <input type="text" class="form-control" name="child_name" placeholder="Ketik nama lengkap anak..." required>
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size: 0.9rem;">Umur (Tahun):</label>
                                <input type="number" class="form-control" name="age" min="1" max="20" placeholder="Contoh: 8" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-bold" style="font-size: 0.9rem;">Kelas Sekolah:</label>
                                <input type="text" class="form-control" name="school_class" placeholder="Contoh: Kelas 3 SD" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-size: 0.9rem;">No. HP Orang Tua / WhatsApp:</label>
                                <input type="tel" class="form-control" name="phone_number" placeholder="Contoh: 0812XXXXXXXX" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-size: 0.9rem;">Foto Profil:</label>
                                <input type="file" class="form-control" name="child_photo" accept="image/*">
                            </div>

                            <div class="col-12 text-center pt-3">
                                <button type="submit" class="btn btn-submit-fun w-100 py-2.5 fs-6" style="background-color: #ff006e;">✨ Daftarkan Anak Baru</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>