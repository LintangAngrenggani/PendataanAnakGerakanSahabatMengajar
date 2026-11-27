<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/koneksi.php';

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Kredensial akses edit / tambah / hapus proker
const EDIT_USERNAME = 'koneksi';
const EDIT_PASSWORD = 'koneksioke';

$pesanNotifikasi = '';
$bukaModalLogin = false;

// --- PROSES LOGIN AKSES EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login_edit') {
    $inputUser = trim((string)($_POST['username'] ?? ''));
    $inputPass = (string)($_POST['password'] ?? '');

    if (hash_equals(EDIT_USERNAME, $inputUser) && hash_equals(EDIT_PASSWORD, $inputPass)) {
        $_SESSION['akses_edit_gsm'] = true;
        $pesanNotifikasi = '🔓 Akses edit proker berhasil dibuka!';
    } else {
        $_SESSION['akses_edit_gsm'] = false;
        $pesanNotifikasi = '🚫 Username atau password salah! Akses edit ditolak.';
        $bukaModalLogin = true;
    }
}

// --- PROSES LOGOUT AKSES EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout_edit') {
    unset($_SESSION['akses_edit_gsm']);
    $pesanNotifikasi = '🔒 Akses edit proker telah dikunci kembali.';
}

$punyaAksesEdit = !empty($_SESSION['akses_edit_gsm']);

// 1. PROSES TAMBAH PROKER BARU (SUDAH DISINKRONKAN KE 3 KOLOM)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_proker') {
    if (!$punyaAksesEdit) {
        $pesanNotifikasi = '🔒 Masukkan username & password terlebih dahulu untuk menambah proker!';
        $bukaModalLogin = true;
    } else {
        $namaProker = trim((string)($_POST['nama_proker'] ?? ''));
        $tanggal = trim((string)($_POST['tanggal_pelaksanaan'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'Direncanakan'));

        if ($namaProker !== '' && $tanggal !== '') {
            try {
                $stmt = $pdo->prepare('INSERT INTO program_kerja (nama_proker, tanggal_pelaksanaan, status) VALUES (?, ?, ?)');
                $stmt->execute([$namaProker, $tanggal, $status]);
                $pesanNotifikasi = '✨ Proker berhasil disimpan dan dihubungkan ke tanggal sesi pengajaran!';
            } catch (Throwable $e) {
                $pesanNotifikasi = '🚫 Gagal menyimpan proker: ' . $e->getMessage();
            }
        } else {
            $pesanNotifikasi = '⚠️ Nama Proker dan Pilihan Tanggal Sesi wajib diisi!';
        }
    }
}

// 2. PROSES HAPUS PROKER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_proker') {
    if (!$punyaAksesEdit) {
        $pesanNotifikasi = '🔒 Masukkan username & password terlebih dahulu untuk menghapus proker!';
        $bukaModalLogin = true;
    } else {
        $idProker = (int)($_POST['id'] ?? 0);
        if ($idProker > 0) {
            try {
                $stmtDelete = $pdo->prepare('DELETE FROM program_kerja WHERE id = ?');
                $stmtDelete->execute([$idProker]);
                $pesanNotifikasi = '🗑️ Program kerja berhasil dihapus!';
            } catch (Throwable $e) {
                $pesanNotifikasi = '🚫 Gagal menghapus proker!';
            }
        }
    }
}

// 3. AMBIL DAFTAR TANGGAL SESI PENGAJARAN
try {
    $queryTanggal = $pdo->query('SELECT session_date FROM attendance_records GROUP BY session_date ORDER BY session_date DESC');
    $daftarSesiPengajaran = $queryTanggal->fetchAll();
} catch (Throwable $e) {
    $daftarSesiPengajaran = [];
}

// 4. AMBIL SEMUA DATA PROGRAM KERJA
try {
    $queryProker = $pdo->query('SELECT * FROM program_kerja ORDER BY tanggal_pelaksanaan DESC');
    $daftarProker = $queryProker->fetchAll();
} catch (Throwable $e) {
    $daftarProker = [];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agenda Program Kerja - GSMConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f3fbf7; color: #1e293b; font-family: 'Nunito', sans-serif; }
        h1, h2, h3, h4, .brand-title { font-family: 'Fredoka', sans-serif; font-weight: 700; }
        .navbar-fun { background-color: #ffffff; border-bottom: 4px solid #1e293b; box-shadow: 0 4px 0px #1e293b; }
        .bubble-card { background: #ffffff; border: 3px solid #1e293b; border-radius: 24px; box-shadow: 5px 5px 0px #1e293b; }
        .input-fun { border: 3px solid #1e293b !important; border-radius: 12px; padding: 10px; font-weight: 700; }
        .input-fun:focus { border-color: #ff006e !important; box-shadow: none; }
        .btn-submit-fun { font-family: 'Fredoka', sans-serif; font-weight: 700; border: 3px solid #1e293b; border-radius: 16px; box-shadow: 4px 4px 0px #1e293b; color: white !important; transition: all 0.05s ease-in-out; text-decoration: none; }
        .btn-submit-fun:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0px #1e293b; }
        .badge-status { border: 2px solid #1e293b; box-shadow: 2px 2px 0px #1e293b; font-weight: 900; border-radius: 8px; }
        .btn-header-nav { font-family: 'Fredoka', sans-serif; border: 3px solid #1e293b; border-radius: 14px; font-weight: 700; background: #ffbe0b; box-shadow: 3px 3px 0px #1e293b; color: #1e293b; text-decoration: none; padding: 8px 16px; transition: all 0.05s ease-in-out; }
        .btn-header-nav:hover { transform: translate(-1px, -1px); box-shadow: 4px 4px 0px #1e293b; }
        
        /* Style Tabel */
        .table-fun { border: 3px solid #1e293b !important; border-radius: 12px; overflow: hidden; border-collapse: separate; border-spacing: 0; }
        .table-fun th { background-color: #1e293b !important; color: white !important; border: none; padding: 12px; text-align: center; }
        .table-fun th.text-start-th { text-align: left; }
        .table-fun td { border-bottom: 2px solid #1e293b; border-right: 2px solid #e2e8f0; padding: 12px; background-color: #ffffff; }
        .table-fun td:last-child { border-right: none; }
        .table-fun tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-fun py-3 mb-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-4 fs-md-3 brand-title text-dark m-0">📋 Proker pada Pengajaran</span>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($punyaAksesEdit): ?>
                <form method="POST" action="proker.php" class="d-inline">
                    <input type="hidden" name="action" value="logout_edit">
                    <button type="submit" class="btn-header-nav" style="background-color: #9bf6ff;">🔓 Kunci Edit</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn-header-nav" style="background-color: #9bf6ff;" data-bs-toggle="modal" data-bs-target="#modalLoginEdit">
                    🔐 Login Edit
                </button>
            <?php endif; ?>
            <a href="index.php" class="btn-header-nav">🏠 Menu Utama</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <?php if ($pesanNotifikasi !== ''): ?>
        <div class="alert alert-dark border-3 border-dark fw-bold mb-4" role="alert">
            📢 <?= proteksi($pesanNotifikasi) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="bubble-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="m-0 text-dark">✨ Hubungkan Proker</h4>
                    <span class="badge <?= $punyaAksesEdit ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                        <?= $punyaAksesEdit ? '🔓 AKTIF' : '🔒 TERKUNCI' ?>
                    </span>
                </div>
                <form method="POST" action="proker.php">
                    <input type="hidden" name="action" value="tambah_proker">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nama Kegiatan / Proker</label>
                        <input type="text" class="form-control input-fun" name="nama_proker" placeholder="Contoh: Raport Anak" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Hubungkan ke Sesi Tanggal</label>
                        <select class="form-select input-fun" name="tanggal_pelaksanaan" required>
                            <option value="">-- Pilih Sesi Pengajaran --</option>
                            <?php if (count($daftarSesiPengajaran) === 0): ?>
                                <option value="" disabled>Belum ada tanggal sesi mengajar di absensi</option>
                            <?php else: ?>
                                <?php foreach ($daftarSesiPengajaran as $sesi): ?>
                                    <?php $tglFormataed = date('d M Y', strtotime((string)$sesi['session_date'])); ?>
                                    <option value="<?= proteksi((string)$sesi['session_date']) ?>">
                                        📅 Sesi Tanggal: <?= $tglFormataed ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted mt-1 d-block" style="font-size:0.75rem;">*Daftar ini otomatis mengambil tanggal dari Riwayat Sesi Pengajaran.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Status</label>
                        <select class="form-select input-fun" name="status">
                            <option value="Direncanakan">📅 Direncanakan</option>
                            <option value="Berjalan">⚡ Sedang Berjalan</option>
                            <option value="Selesai">✅ Selesai</option>
                        </select>
                    </div>

                    <?php if ($punyaAksesEdit): ?>
                        <button type="submit" class="btn btn-submit-fun w-100 py-2" style="background-color: #ff006e;">💾 Simpan & Hubungkan</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-submit-fun w-100 py-2" style="background-color: #ff006e;" data-bs-toggle="modal" data-bs-target="#modalLoginEdit">
                            🔐 Login untuk Simpan Proker
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="bubble-card p-4 h-100">
                <h4 class="mb-4 text-dark">📋 Hubungan Proker & Sesi Pengajaran</h4>
                <div class="table-responsive">
                    <table class="table table-fun align-middle m-0">
                        <thead>
                            <tr>
                                <th class="text-start-th" style="width: 45%;">Nama Kegiatan</th>
                                <th style="width: 25%;">Sesi Tanggal</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daftarProker) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted fw-bold">Belum ada agenda proker yang dihubungkan dengan sesi pengajaran.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daftarProker as $pk): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= proteksi((string)$pk['nama_proker']) ?></td>
                                        <td class="text-center fw-bold text-primary">
                                            📅 <?= date('d-m-Y', strtotime((string)$pk['tanggal_pelaksanaan'])) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $badgeColor = 'bg-secondary text-white';
                                            if ($pk['status'] === 'Berjalan') $badgeColor = 'bg-warning text-dark';
                                            if ($pk['status'] === 'Selesai') $badgeColor = 'bg-success text-white';
                                            ?>
                                            <span class="badge badge-status p-2 <?= $badgeColor ?>">
                                                <?= strtoupper($pk['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($punyaAksesEdit): ?>
                                                <form method="POST" action="proker.php" onsubmit="return confirm('Hapus program kerja ini?');">
                                                    <input type="hidden" name="action" value="hapus_proker">
                                                    <input type="hidden" name="id" value="<?= (int)$pk['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm border-2 border-dark fw-bold" style="border-radius: 8px;">🗑️ Hapus</button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-danger btn-sm border-2 border-dark fw-bold" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalLoginEdit">
                                                    🔐 Hapus
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL LOGIN AKSES EDIT -->
<div class="modal fade" id="modalLoginEdit" tabindex="-1" aria-labelledby="modalLoginEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bubble-card p-2">
            <div class="modal-header border-bottom border-3 border-dark">
                <h5 class="modal-title" id="modalLoginEditLabel" style="font-family: 'Fredoka';">🔐 Verifikasi Akses Edit Proker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="proker.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="login_edit">
                    <p class="small text-muted fw-bold mb-3">Masukkan username dan password untuk menambah atau menghapus program kerja.</p>
                    <div class="mb-3">
                        <label for="inputUsername" class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control input-fun" id="inputUsername" name="username" placeholder="Masukkan username" required autocomplete="username">
                    </div>
                    <div class="mb-2">
                        <label for="inputPassword" class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control input-fun" id="inputPassword" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer border-top border-3 border-dark">
                    <button type="button" class="btn btn-outline-dark fw-bold rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-submit-fun px-4 py-2" style="background-color: #06d6a0;">🔓 Buka Akses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($bukaModalLogin): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalLoginEdit');
    if (modalEl) {
        new bootstrap.Modal(modalEl).show();
    }
});
</script>
<?php endif; ?>
</body>
</html>
