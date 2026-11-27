<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/koneksi.php';

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Kredensial akses edit / hapus
const EDIT_USERNAME = 'koneksi';
const EDIT_PASSWORD = 'koneksioke';

$pesanNotifikasi = '';
$tipeNotif = 'danger';
$bukaModalLogin = false;

// --- PROSES LOGIN AKSES EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login_edit') {
    $inputUser = trim((string)($_POST['username'] ?? ''));
    $inputPass = (string)($_POST['password'] ?? '');

    if (hash_equals(EDIT_USERNAME, $inputUser) && hash_equals(EDIT_PASSWORD, $inputPass)) {
        $_SESSION['akses_edit_gsm'] = true;
        $pesanNotifikasi = '🔓 Akses edit berhasil dibuka!';
        $tipeNotif = 'success';
    } else {
        $_SESSION['akses_edit_gsm'] = false;
        $pesanNotifikasi = '🚫 Username atau password salah! Akses edit ditolak.';
        $tipeNotif = 'danger';
        $bukaModalLogin = true;
    }
}

// --- PROSES LOGOUT AKSES EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout_edit') {
    unset($_SESSION['akses_edit_gsm']);
    $pesanNotifikasi = '🔒 Akses edit telah dikunci kembali.';
    $tipeNotif = 'warning';
}

$punyaAksesEdit = !empty($_SESSION['akses_edit_gsm']);

// --- AMBIL REKAP KEHADIRAN PER TANGGAL SESI (HANYA ANAK YANG HADIR) ---
$rekapPerTanggal = [];
$rataRataHadirPerSesi = 0.0;
$maxHadirTertinggi = 1;

try {
    $sqlRekapTgl = "
        SELECT 
            r.session_date,
            r.checklist_json,
            p.nama_proker
        FROM attendance_records r
        LEFT JOIN program_kerja p ON r.session_date = p.tanggal_pelaksanaan
        ORDER BY r.session_date ASC
    ";
    $stmtRekapTgl = $pdo->query($sqlRekapTgl);
    $semuaRecord = $stmtRekapTgl->fetchAll();

    foreach ($semuaRecord as $rec) {
        $tgl = (string)$rec['session_date'];
        if (!isset($rekapPerTanggal[$tgl])) {
            $rekapPerTanggal[$tgl] = [
                'tanggal' => $tgl,
                'proker' => (string)($rec['nama_proker'] ?? '-'),
                'hadir' => 0,
            ];
        }

        $json = json_decode((string)$rec['checklist_json'], true);
        $isHadir = is_array($json)
            ? (array_key_exists('attendance', $json) ? (bool)$json['attendance'] : true)
            : true;

        if ($isHadir) {
            $rekapPerTanggal[$tgl]['hadir']++;
        }
    }

    $totalSesi = count($rekapPerTanggal);
    $sumHadir = 0;
    $tertinggi = 0;

    foreach ($rekapPerTanggal as $item) {
        $sumHadir += $item['hadir'];
        if ($item['hadir'] > $tertinggi) {
            $tertinggi = $item['hadir'];
        }
    }

    if ($totalSesi > 0) {
        $rataRataHadirPerSesi = round($sumHadir / $totalSesi, 1);
    }
    if ($tertinggi > 0) {
        $maxHadirTertinggi = $tertinggi;
    }
} catch (Throwable $e) {
    $rekapPerTanggal = [];
}

// --- FITUR DOWNLOAD LAPORAN & DIAGRAM KEHADIRAN PER TANGGAL (EXCEL .XLS) ---
if (isset($_GET['export']) && $_GET['export'] === 'rekap_tanggal') {
    $namaFile = 'Rekap_Kehadiran_Per_Tanggal_GSM_' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    echo "\xEF\xBB\xBF";
    ?>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #2b2d42; padding: 8px 12px; }
            th { background-color: #ffb703; color: #000000; font-weight: bold; text-align: center; }
            .num { text-align: center; }
            .summary { background-color: #e8dbfc; font-weight: bold; }
            .bar-wrap { background-color: #edf2f4; width: 220px; border: 1px solid #999; }
            .bar-fill { background-color: #06d6a0; height: 18px; color: #000; font-size: 11px; font-weight: bold; text-align: left; padding-left: 6px; }
        </style>
    </head>
    <body>
        <h2>LAPORAN REKAP & DIAGRAM KEHADIRAN PER TANGGAL SESI - GSM</h2>
        <p>Dicetak pada: <?= date('d-m-Y H:i') ?> | Jumlah Tanggal Sesi: <b><?= count($rekapPerTanggal) ?> Tanggal</b></p>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Sesi</th>
                    <th>Kegiatan Proker</th>
                    <th>Jumlah Hadir (Anak)</th>
                    <th>Diagram Batang Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekapPerTanggal)): ?>
                    <tr><td colspan="5" class="num">Belum ada data sesi pengajaran.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($rekapPerTanggal as $row): ?>
                        <?php $lebarPersen = (int)round(($row['hadir'] / $maxHadirTertinggi) * 100); ?>
                        <tr>
                            <td class="num"><?= $no++ ?></td>
                            <td class="num"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= proteksi($row['proker'] !== '' ? $row['proker'] : '-') ?></td>
                            <td class="num"><b><?= $row['hadir'] ?> Anak</b></td>
                            <td>
                                <div class="bar-wrap">
                                    <div class="bar-fill" style="width: <?= $lebarPersen ?>%;"><?= $row['hadir'] ?> anak</div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="summary">
                        <td colspan="3" style="text-align: right;">RATA-RATA KEHADIRAN (DARI <?= count($rekapPerTanggal) ?> TANGGAL):</td>
                        <td class="num"><?= number_format($rataRataHadirPerSesi, 1, ',', '.') ?> Anak / Sesi</td>
                        <td>Total Hadir: <?= array_sum(array_column($rekapPerTanggal, 'hadir')) ?> Anak</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// --- PROSES HAPUS (DELETE) DATA ABSENSI JIKA TOMBOL DIKLIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_absen') {
    if (!$punyaAksesEdit) {
        $pesanNotifikasi = '🔒 Masukkan username & password terlebih dahulu untuk menghapus data!';
        $tipeNotif = 'danger';
        $bukaModalLogin = true;
    } else {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $sessionDate = trim((string)($_POST['session_date'] ?? ''));

        if ($studentId > 0 && $sessionDate !== '') {
            try {
                $stmtDelete = $pdo->prepare('DELETE FROM attendance_records WHERE student_id = ? AND session_date = ?');
                $stmtDelete->execute([$studentId, $sessionDate]);
                
                $pesanNotifikasi = '🗑️ Data absensi anak berhasil dihapus dari sistem!';
                $tipeNotif = 'success';
            } catch (Throwable $e) {
                $pesanNotifikasi = '🚫 Gagal menghapus data: ' . $e->getMessage();
                $tipeNotif = 'danger';
            }
        }
    }
}

try {
    $ambilTanggal = $pdo->query('SELECT session_date FROM attendance_records GROUP BY session_date ORDER BY session_date DESC');
    $kumpulanTanggal = $ambilTanggal->fetchAll();
} catch (Throwable $e) {
    $kumpulanTanggal = [];
}

$tanggalTerpilih = (string)($_GET['view_date'] ?? ($kumpulanTanggal[0]['session_date'] ?? null));

$barisData = [];
$totalHadir = 0;
$prokerSesiIni = null;

if ($tanggalTerpilih !== null && $tanggalTerpilih !== '') {
    try {
        $stmtDetail = $pdo->prepare('SELECT r.student_id, r.checklist_json, s.child_name, s.photo_path FROM attendance_records r JOIN students s ON r.student_id = s.id WHERE r.session_date = ? ORDER BY s.child_name ASC');
        $stmtDetail->execute([$tanggalTerpilih]);
        $barisData = $stmtDetail->fetchAll();
        $totalHadir = count($barisData);
    } catch (Throwable $e) {
        $barisData = [];
        $totalHadir = 0;
    }

    try {
        $stmtProker = $pdo->prepare('SELECT nama_proker, deskripsi, status FROM program_kerja WHERE tanggal_pelaksanaan = ? LIMIT 1');
        $stmtProker->execute([$tanggalTerpilih]);
        $prokerSesiIni = $stmtProker->fetch();
    } catch (Throwable $e) {
        $prokerSesiIni = null;
    }
}

// Refresh data rekap setelah kemungkinan hapus data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus_absen' && $punyaAksesEdit) {
    $rekapPerTanggal = [];
    $rataRataHadirPerSesi = 0.0;
    $maxHadirTertinggi = 1;
    try {
        $stmtRekapTgl = $pdo->query("
            SELECT r.session_date, r.checklist_json, p.nama_proker
            FROM attendance_records r
            LEFT JOIN program_kerja p ON r.session_date = p.tanggal_pelaksanaan
            ORDER BY r.session_date ASC
        ");
        foreach ($stmtRekapTgl->fetchAll() as $rec) {
            $tgl = (string)$rec['session_date'];
            if (!isset($rekapPerTanggal[$tgl])) {
                $rekapPerTanggal[$tgl] = ['tanggal' => $tgl, 'proker' => (string)($rec['nama_proker'] ?? '-'), 'hadir' => 0];
            }
            $json = json_decode((string)$rec['checklist_json'], true);
            $isHadir = is_array($json) ? (array_key_exists('attendance', $json) ? (bool)$json['attendance'] : true) : true;
            if ($isHadir) $rekapPerTanggal[$tgl]['hadir']++;
        }
        $totalSesi = count($rekapPerTanggal);
        $sumHadir = array_sum(array_column($rekapPerTanggal, 'hadir'));
        $tertinggi = !empty($rekapPerTanggal) ? max(array_column($rekapPerTanggal, 'hadir')) : 0;
        if ($totalSesi > 0) $rataRataHadirPerSesi = round($sumHadir / $totalSesi, 1);
        if ($tertinggi > 0) $maxHadirTertinggi = $tertinggi;
    } catch (Throwable $e) {}
}

// Siapkan data JSON untuk Chart.js
$chartLabels = [];
$chartHadir = [];
foreach ($rekapPerTanggal as $item) {
    $chartLabels[] = date('d M Y', strtotime($item['tanggal']));
    $chartHadir[] = $item['hadir'];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arsip Sesi Pengajaran - GSM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        body { background-color: #edf2f4; color: #2b2d42; font-family: 'Nunito', sans-serif; }
        h1, h2, h3, .title-fun { font-family: 'Fredoka', sans-serif; }
        .bubble-card { background: #ffffff; border: 3px solid #2b2d42; border-radius: 24px; box-shadow: 4px 4px 0px #2b2d42; }
        .list-group-fun .list-group-item { border: 2px solid #2b2d42; margin-bottom: 8px; border-radius: 12px !important; font-weight: 700; }
        .list-group-fun .active { background-color: #ffb703 !important; color: black !important; }
        
        /* Badge Status */
        .badge-status { border: 2px solid #2b2d42; box-shadow: 2px 2px 0px #2b2d42; font-weight: 900; border-radius: 8px; font-size: 0.85rem; }
        
        /* Style Neubrutalism Proker Banner */
        .proker-banner {
            background-color: #e8dbfc;
            border: 3px solid #2b2d42;
            border-radius: 16px;
            box-shadow: 4px 4px 0px #2b2d42;
        }

        /* Style Tombol Download Neubrutalism */
        .btn-download-fun {
            background-color: #06d6a0;
            color: #2b2d42 !important;
            border: 2px solid #2b2d42;
            border-radius: 12px;
            box-shadow: 2px 2px 0px #2b2d42;
            font-weight: 800;
            transition: all 0.05s ease-in-out;
        }
        .btn-download-fun:hover {
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px #2b2d42;
            background-color: #05c08f;
        }

        /* Style Tombol Auth Neubrutalism */
        .btn-auth-fun {
            border: 2px solid #2b2d42;
            border-radius: 12px;
            box-shadow: 2px 2px 0px #2b2d42;
            font-weight: 800;
            transition: all 0.05s ease-in-out;
        }
        .btn-auth-fun:hover {
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px #2b2d42;
        }

        /* Style Tombol Hapus Neubrutalism */
        .btn-hapus-fun {
            font-family: 'Fredoka', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            border: 2px solid #2b2d42;
            border-radius: 10px;
            padding: 4px 12px;
            box-shadow: 2px 2px 0px #2b2d42;
            background-color: #ff006e;
            color: white !important;
            transition: all 0.05s ease-in-out;
        }
        .btn-hapus-fun:hover {
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px #2b2d42;
            background-color: #e0005a;
        }
        .btn-hapus-fun:active {
            transform: translate(1px, 1px);
            box-shadow: 1px 1px 0px #2b2d42;
        }
        .notif-toast {
            border: 3px solid #2b2d42;
            box-shadow: 3px 3px 0px #2b2d42;
            border-radius: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom border-3 border-dark py-3 mb-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="navbar-brand fs-3 title-fun text-dark m-0">📊 Rekap Sesi Pengajaran</span>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($punyaAksesEdit): ?>
                <form method="POST" action="recap.php<?= $tanggalTerpilih ? '?view_date=' . urlencode($tanggalTerpilih) : '' ?>" class="d-inline">
                    <input type="hidden" name="action" value="logout_edit">
                    <button type="submit" class="btn btn-auth-fun" style="background-color: #ffd166; color: #2b2d42;">🔓 Kunci Edit</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn btn-auth-fun" style="background-color: #ffd166; color: #2b2d42;" data-bs-toggle="modal" data-bs-target="#modalLoginEdit">
                    🔐 Login Edit
                </button>
            <?php endif; ?>
            <a href="recap.php?export=rekap_tanggal" class="btn btn-download-fun">📥 Download Rekap & Diagram (.xls)</a>
            <a href="proker.php" class="btn fw-bold style-nav" style="background-color: #9b5de5; color: white; border: 2px solid #2b2d42; border-radius:12px; box-shadow: 2px 2px 0px #2b2d42;">📋 Agenda Proker</a>
            <a href="index.php" class="btn btn-outline-dark fw-bold" style="border-radius:12px; border-width: 2px;">← Ke Form Input</a>
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
        <div class="alert notif-toast alert-dismissible fade show p-3 mb-4" style="background-color: <?= $bgNotif ?>; color: #2b2d42;" role="alert">
            <?= proteksi($pesanNotifikasi) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- DIAGRAM KEHADIRAN PER TANGGAL -->
    <?php if (!empty($rekapPerTanggal)): ?>
        <div class="bubble-card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="title-fun text-dark m-0">📈 Diagram Kehadiran per Tanggal</h4>
                    <small class="text-muted fw-bold">
                        Dari <?= count($rekapPerTanggal) ?> Tanggal Sesi | Rata-rata Hadir: 
                        <span class="text-success"><?= number_format($rataRataHadirPerSesi, 1, ',', '.') ?> Anak / Tanggal</span>
                    </small>
                </div>
                <div>
                    <button type="button" id="btnDownloadChart" class="btn btn-sm fw-bold" style="background-color: #ffb703; border: 2px solid #2b2d42; border-radius: 10px; box-shadow: 2px 2px 0px #2b2d42;">
                        🖼️ Simpan Gambar Diagram
                    </button>
                </div>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="chartKehadiranTanggal"></canvas>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-md-4">
            <div class="bubble-card p-3">
                <h5 class="title-fun mb-3">📅 Pilih Tanggal Kelas:</h5>
                <div class="list-group list-group-fun">
                    <?php foreach ($kumpulanTanggal as $tgl): ?>
                        <?php 
                            $dt = (string)$tgl['session_date'];
                            $jmlHadirTgl = $rekapPerTanggal[$dt]['hadir'] ?? 0;
                        ?>
                        <a href="recap.php?view_date=<?= urlencode($dt) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= ($dt === $tanggalTerpilih) ? 'active' : '' ?>">
                            <span>📅 <?= date('d-m-Y', strtotime($dt)) ?></span>
                            <span class="badge bg-dark text-white rounded-pill"><?= $jmlHadirTgl ?> Hadir</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="bubble-card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="title-fun text-dark m-0">Pengajaran Tanggal: <span class="text-primary font-monospace"><?= $tanggalTerpilih ? date('d M Y', strtotime($tanggalTerpilih)) : '-' ?></span></h4>
                    <span class="badge badge-status p-2 <?= $punyaAksesEdit ? 'bg-success text-white' : 'bg-secondary text-white' ?>">
                        <?= $punyaAksesEdit ? '🔓 MODE EDIT AKTIF' : '🔒 MODE BACA SAJA' ?>
                    </span>
                </div>
                
                <?php if ($prokerSesiIni): ?>
                    <div class="proker-banner p-3 mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <div>
                            <div class="fs-6 fw-bold text-dark" style="font-family: 'Fredoka';">📋 Terhubung Kegiatan Proker:</div>
                            <h5 class="text-primary m-0 fw-black text-uppercase mt-1" style="font-family: 'Fredoka';"><?= proteksi((string)$prokerSesiIni['nama_proker']) ?></h5>
                            <?php if (!empty($prokerSesiIni['deskripsi'])): ?>
                                <small class="text-muted d-block mt-1">📌 Catatan: <?= proteksi((string)$prokerSesiIni['deskripsi']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php
                            $colorBadge = 'bg-secondary';
                            if ($prokerSesiIni['status'] === 'Berjalan') $colorBadge = 'bg-warning text-dark';
                            if ($prokerSesiIni['status'] === 'Selesai') $colorBadge = 'bg-success text-white';
                            ?>
                            <span class="badge badge-status p-2 <?= $colorBadge ?>">
                                Proker: <?= strtoupper($prokerSesiIni['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border border-2 text-muted mb-4 py-2" style="border-radius: 12px; font-size: 0.85rem;">
                        💡 *Tidak ada agenda kegiatan program kerja (Proker) khusus yang terhubung di sesi tanggal ini.*
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <div class="p-3 bg-success bg-opacity-20 rounded-4 border border-dark fw-bold text-center fs-5">
                        🟢 Total Anak Hadir: <?= $totalHadir ?> Anak
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="table-dark">
                                <th>Nama Anak Hadir</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($barisData) === 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted fw-bold">Belum ada rekaman kehadiran pada tanggal ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($barisData as $b): ?>
                                    <tr>
                                        <td class="fw-bold text-capitalize text-dark"><?= proteksi((string)$b['child_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-status p-2 bg-success text-white">
                                                ✅ HADIR
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($punyaAksesEdit): ?>
                                                <form method="POST" action="recap.php?view_date=<?= urlencode((string)$tanggalTerpilih) ?>" onsubmit="return confirm('Apakah kamu yakin ingin menghapus absensi <?= proteksi((string)$b['child_name']) ?> pada tanggal ini?');" class="d-inline">
                                                    <input type="hidden" name="action" value="hapus_absen">
                                                    <input type="hidden" name="student_id" value="<?= (int)$b['student_id'] ?>">
                                                    <input type="hidden" name="session_date" value="<?= proteksi((string)$tanggalTerpilih) ?>">
                                                    <button type="submit" class="btn btn-hapus-fun">🗑️ Hapus Absen</button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-hapus-fun" data-bs-toggle="modal" data-bs-target="#modalLoginEdit">
                                                    🔐 Hapus Absen
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
            <div class="modal-header border-bottom border-2 border-dark">
                <h5 class="modal-title title-fun" id="modalLoginEditLabel">🔐 Verifikasi Akses Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="recap.php<?= $tanggalTerpilih ? '?view_date=' . urlencode($tanggalTerpilih) : '' ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" value="login_edit">
                    <p class="small text-muted fw-bold mb-3">Masukkan username dan password untuk mengaktifkan akses ubah/hapus data.</p>
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
                    <button type="submit" class="btn btn-download-fun">🔓 Buka Akses Edit</button>
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
        const modalInstance = new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
});
</script>
<?php endif; ?>

<?php if (!empty($rekapPerTanggal)): ?>
<script>
const ctx = document.getElementById('chartKehadiranTanggal').getContext('2d');
const chartKehadiran = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Jumlah Anak Hadir',
                data: <?= json_encode($chartHadir) ?>,
                backgroundColor: '#06d6a0',
                borderColor: '#2b2d42',
                borderWidth: 2,
                borderRadius: 8
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                ticks: { font: { family: 'Nunito', weight: 'bold' }, color: '#2b2d42' }
            },
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { family: 'Nunito', weight: 'bold' }, color: '#2b2d42' },
                title: { display: true, text: 'Jumlah Anak Hadir', font: { family: 'Fredoka', size: 13 } }
            }
        }
    }
});

document.getElementById('btnDownloadChart').addEventListener('click', function () {
    const link = document.createElement('a');
    link.download = 'Diagram_Kehadiran_GSM_<?= date('Y-m-d') ?>.png';
    link.href = chartKehadiran.toBase64Image();
    link.click();
});
</script>
<?php endif; ?>

</body>
</html>
