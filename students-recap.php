<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/koneksi.php';

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Kredensial akses edit
const EDIT_USERNAME = 'koneksi';
const EDIT_PASSWORD = 'koneksioke';

$pesanNotifikasi = '';
$tipeNotif = 'danger';
$bukaModalLogin = false;
$tujuanEditSetelahLogin = '';

// --- PROSES LOGIN AKSES EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login_edit') {
    $inputUser = trim((string)($_POST['username'] ?? ''));
    $inputPass = (string)($_POST['password'] ?? '');
    $redirectId = (int)($_POST['redirect_student_id'] ?? 0);

    if (hash_equals(EDIT_USERNAME, $inputUser) && hash_equals(EDIT_PASSWORD, $inputPass)) {
        $_SESSION['akses_edit_gsm'] = true;
        if ($redirectId > 0) {
            header('Location: edit-student.php?id=' . $redirectId);
            exit;
        }
        $pesanNotifikasi = '🔓 Akses edit berhasil dibuka!';
        $tipeNotif = 'success';
    } else {
        $_SESSION['akses_edit_gsm'] = false;
        $pesanNotifikasi = '🚫 Username atau password salah! Akses edit ditolak.';
        $tipeNotif = 'danger';
        $bukaModalLogin = true;
        if ($redirectId > 0) {
            $tujuanEditSetelahLogin = (string)$redirectId;
        }
    }
}

// --- PROSES LOGOUT AKSES EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout_edit') {
    unset($_SESSION['akses_edit_gsm']);
    $pesanNotifikasi = '🔒 Akses edit telah dikunci kembali.';
    $tipeNotif = 'warning';
}

$punyaAksesEdit = !empty($_SESSION['akses_edit_gsm']);

try {
    $query = $pdo->query('SELECT id, child_name, age, school_class, phone_number, photo_path FROM students ORDER BY child_name ASC');
    $semuaSiswa = $query->fetchAll();

    $queryAbsen = $pdo->query('SELECT student_id, checklist_json FROM attendance_records');
    $semuaAbsen = $queryAbsen->fetchAll();

    $hitungHadir = [];
    foreach ($semuaAbsen as $absen) {
        $idSiswa = (int)$absen['student_id'];
        $dataJson = json_decode((string)$absen['checklist_json'], true);
        
        if (!isset($hitungHadir[$idSiswa])) {
            $hitungHadir[$idSiswa] = 0;
        }
        
        if (is_array($dataJson) && (bool)($dataJson['attendance'] ?? false)) {
            $hitungHadir[$idSiswa]++;
        }
    }

    $totalAnakAktif = count($semuaSiswa);
} catch (Throwable $e) {
    die('Waduh, gagal memuat kompilasi data: ' . $e->getMessage());
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Profil Lengkap Anak - GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #1e293b;
            font-family: 'Nunito', sans-serif;
        }
        h1, h2, h3, .brand-title {
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
        }
        .bubble-card {
            background: #ffffff;
            border: 3px solid #1e293b;
            border-radius: 24px;
            box-shadow: 4px 4px 0px #1e293b;
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
        .btn-header-nav:hover {
            box-shadow: 5px 5px 0px #1e293b;
            transform: translate(-1px, -1px);
        }
        .btn-auth-fun {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            border: 3px solid #1e293b;
            border-radius: 14px;
            padding: 10px 18px;
            box-shadow: 4px 4px 0px #1e293b;
            background-color: #9bf6ff;
            color: #1e293b !important;
            transition: all 0.1s ease-in-out;
        }
        .btn-auth-fun:hover {
            box-shadow: 5px 5px 0px #1e293b;
            transform: translate(-1px, -1px);
        }
        .avatar-frame {
            width: 85px;
            height: 85px;
            object-fit: cover;
            border-radius: 20px;
            border: 3px solid #1e293b;
        }
        .badge-hadir {
            background-color: #3a86ff;
            color: white;
            border: 2px solid #1e293b;
            box-shadow: 2px 2px 0px #1e293b;
            font-size: 0.9rem;
            font-weight: 900;
        }
        .detail-text {
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 2px;
            font-weight: bold;
        }
        .btn-excel-fun {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            border: 3px solid #1e293b;
            border-radius: 14px;
            padding: 8px 18px;
            box-shadow: 3px 3px 0px #1e293b;
            background-color: #06d6a0;
            color: white !important;
            text-decoration: none;
            display: inline-block;
            transition: all 0.1s ease-in-out;
        }
        .btn-excel-fun:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0px #1e293b;
            background-color: #05b88a;
        }
        /* Style Tombol Edit Berdesain Neubrutalism */
        .btn-edit-fun {
            font-family: 'Fredoka', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            border: 2px solid #1e293b;
            border-radius: 10px;
            padding: 4px 10px;
            box-shadow: 2px 2px 0px #1e293b;
            background-color: #ffbe0b;
            color: #1e293b !important;
            text-decoration: none;
            display: inline-block;
            transition: all 0.05s ease-in-out;
        }
        .btn-edit-fun:hover {
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px #1e293b;
            background-color: #e6a900;
        }
        .notif-toast {
            border: 3px solid #1e293b;
            box-shadow: 4px 4px 0px #1e293b;
            border-radius: 16px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-fun py-3 mb-5">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center justify-content-center gap-2 gap-sm-3 flex-wrap">
            <img src="Logo GSM.PNG" alt="Logo GSM" class="img-fluid" style="height: 45px; object-fit: contain;">
            
            <div class="d-flex flex-column text-center text-sm-start">
                <span class="fs-4 fs-sm-3 text-dark brand-title m-0">GSMConnect</span>
                <small class="text-muted fw-bold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Database Anak Aktif Pengajaran</small>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php if ($punyaAksesEdit): ?>
                <form method="POST" action="" class="d-inline">
                    <input type="hidden" name="action" value="logout_edit">
                    <button type="submit" class="btn btn-auth-fun">🔓 Kunci Edit</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn btn-auth-fun" data-bs-toggle="modal" data-bs-target="#modalLoginEdit" data-student-id="">
                    🔐 Login Edit
                </button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-header-nav text-center">🏠 Menu Utama</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <?php if ($pesanNotifikasi !== ''): ?>
        <?php
            $bgNotif = '#ffccd5';
            if ($tipeNotif === 'success') $bgNotif = '#b8f2e6';
            if ($tipeNotif === 'warning') $bgNotif = '#fff3b0';
        ?>
        <div class="alert notif-toast alert-dismissible fade show p-3 mb-4" style="background-color: <?= $bgNotif ?>; color: #1e293b;" role="alert">
            <?= proteksi($pesanNotifikasi) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="p-4 mb-5 text-center bubble-card" style="background-color: #9bf6ff;">
        <h2 class="mb-2 text-dark">✨ Recap Anak & Total Kehadiran ✨</h2>
        <p class="fs-5 mb-3 text-dark opacity-75">Berikut rangkuman data diri anak-anak hebat berserta akumulasi keaktifan mereka kelas!</p>
        
        <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3 mt-2">
            <span class="badge bg-dark fs-5 px-4 py-2 border border-2 border-white">
                Total Terdata: <?= $totalAnakAktif ?> Anak Aktif
            </span>
            <a href="download-excel.php" class="btn-excel-fun">🟢 Download Data ke Excel</a>
        </div>
    </div>

    <div class="row g-4">
        <?php if (count($semuaSiswa) === 0): ?>
            <div class="col-12 text-center py-5">
                <div class="bubble-card p-5 d-inline-block">
                    <p class="fs-4 mb-0">Belum ada data anak aktif yang terdaftar di sistem. 🌱</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($semuaSiswa as $siswa): ?>
                <?php 
                    $idSiswa = (int)$siswa['id'];
                    $jumlahKehadiran = $hitungHadir[$idSiswa] ?? 0;
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="bubble-card p-3 d-flex align-items-start gap-3">
                        <div>
                            <?php if (!empty($siswa['photo_path']) && file_exists(__DIR__ . '/uploads/' . $siswa['photo_path'])): ?>
                                <img src="uploads/<?= proteksi($siswa['photo_path']) ?>" alt="Foto" class="avatar-frame">
                            <?php else: ?>
                                <div class="avatar-frame d-flex align-items-center justify-content-center bg-warning fs-3">🧸</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-grow-1">
                            <h5 class="mb-2 text-dark fw-bold text-capitalize" style="font-family:'Fredoka';">
                                <?= proteksi((string)$siswa['child_name']) ?>
                            </h5>
                            
                            <div class="detail-text">🎂 Umur: <?= $siswa['age'] > 0 ? $siswa['age'] . ' Tahun' : '-' ?></div>
                            <div class="detail-text">🏫 Kelas: <?= !empty($siswa['school_class']) ? proteksi($siswa['school_class']) : '-' ?></div>
                            <div class="detail-text">📞 HP Ortu: <?= !empty($siswa['phone_number']) ? proteksi($siswa['phone_number']) : '-' ?></div>
                            
                            <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
                                <span class="badge badge-hadir px-3 py-1.5">
                                    🏃‍♂️ Hadir: <?= $jumlahKehadiran ?> Kali
                                </span>
                                <?php if ($punyaAksesEdit): ?>
                                    <a href="edit-student.php?id=<?= $idSiswa ?>" class="btn-edit-fun">✏️ Edit Profil</a>
                                <?php else: ?>
                                    <button type="button" class="btn-edit-fun" data-bs-toggle="modal" data-bs-target="#modalLoginEdit" data-student-id="<?= $idSiswa ?>">
                                        🔐 Edit Profil
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- MODAL LOGIN AKSES EDIT -->
<div class="modal fade" id="modalLoginEdit" tabindex="-1" aria-labelledby="modalLoginEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bubble-card p-2">
            <div class="modal-header border-bottom border-2 border-dark">
                <h5 class="modal-title" id="modalLoginEditLabel" style="font-family:'Fredoka';">🔐 Verifikasi Akses Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="login_edit">
                    <input type="hidden" name="redirect_student_id" id="redirectStudentId" value="<?= proteksi($tujuanEditSetelahLogin) ?>">
                    <p class="small text-muted fw-bold mb-3">Masukkan username dan password untuk mengakses halaman edit profil.</p>
                    <div class="mb-3">
                        <label for="inputUsername" class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control border-2 border-dark rounded-3 fw-bold" id="inputUsername" name="username" placeholder="Masukkan username" required autocomplete="username">
                    </div>
                    <div class="mb-2">
                        <label for="inputPassword" class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control border-2 border-dark rounded-3 fw-bold" id="inputPassword" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer border-top border-2 border-dark">
                    <button type="button" class="btn btn-outline-dark fw-bold rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-excel-fun">🔓 Masuk & Edit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalLoginEl = document.getElementById('modalLoginEdit');
if (modalLoginEl) {
    modalLoginEl.addEventListener('show.bs.modal', function (event) {
        const triggerBtn = event.relatedTarget;
        const redirectInput = document.getElementById('redirectStudentId');
        if (triggerBtn && redirectInput) {
            redirectInput.value = triggerBtn.getAttribute('data-student-id') || '';
        }
    });
}
</script>

<?php if ($bukaModalLogin): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalInstance = new bootstrap.Modal(document.getElementById('modalLoginEdit'));
    modalInstance.show();
});
</script>
<?php endif; ?>

</body>
</html>
