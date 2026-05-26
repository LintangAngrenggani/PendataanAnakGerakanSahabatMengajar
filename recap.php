<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/koneksi.php';

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$pesanNotifikasi = '';

// --- PROSES HAPUS (DELETE) DATA ABSENSI JIKA TOMBOL DIKLIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_absen') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $sessionDate = trim((string)($_POST['session_date'] ?? ''));

    if ($studentId > 0 && $sessionDate !== '') {
        try {
            // Jalankan query DELETE untuk menghapus rekaman absen anak pada tanggal ini
            $stmtDelete = $pdo->prepare('DELETE FROM attendance_records WHERE student_id = ? AND session_date = ?');
            $stmtDelete->execute([$studentId, $sessionDate]);
            
            $pesanNotifikasi = '🗑️ Data absensi anak berhasil dihapus dari sistem!';
        } catch (Throwable $e) {
            $pesanNotifikasi = '🚫 Gagal menghapus data: ' . $e->getMessage();
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
$totalAbsen = 0;
$prokerSesiIni = null; // Menyimpan data proker jika ada

if ($tanggalTerpilih !== null) {
    // 1. AMBIL DETAIL DATA ABSENSI ANAK
    try {
        $stmtDetail = $pdo->prepare('SELECT r.student_id, r.checklist_json, s.child_name, s.photo_path FROM attendance_records r JOIN students s ON r.student_id = s.id WHERE r.session_date = ? ORDER BY s.child_name ASC');
        $stmtDetail->execute([$tanggalTerpilih]);
        $barisData = $stmtDetail->fetchAll();
        
        foreach ($barisData as $data) {
            $json = json_decode((string)$data['checklist_json'], true);
            if (is_array($json) && (bool)($json['attendance'] ?? false)) { $totalHadir++; } else { $totalAbsen++; }
        }
    } catch (Throwable $e) { $barisData = []; }

    // 2. AMBIL DATA PROGRAM KERJA YANG TERHUBUNG DENGAN TANGGAL SESI INI
    try {
        $stmtProker = $pdo->prepare('SELECT nama_proker, deskripsi, status FROM program_kerja WHERE tanggal_pelaksanaan = ? LIMIT 1');
        $stmtProker->execute([$tanggalTerpilih]);
        $prokerSesiIni = $stmtProker->fetch();
    } catch (Throwable $e) {
        $prokerSesiIni = null;
    }
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
            background-color: #ffccd5;
            color: #2b2d42;
            font-weight: bold;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom border-3 border-dark py-3 mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <span class="navbar-brand fs-3 title-fun text-dark m-0">📊 Rekap Sesi Pengajaran</span>
        <div class="d-flex gap-2">
            <a href="proker.php" class="btn btn-purple fw-bold style-nav" style="background-color: #9b5de5; color: white; border: 2px solid #2b2d42; border-radius:12px; box-shadow: 2px 2px 0px #2b2d42;">📋 Agenda Proker</a>
            <a href="index.php" class="btn btn-outline-dark fw-bold" style="border-radius:12px; border-width: 2px;">← Ke Form Input</a>
        </div>
    </div>
</nav>

<main class="container">
    <?php if ($pesanNotifikasi !== ''): ?>
        <div class="alert notif-toast alert-dismissible fade show p-3 mb-4" role="alert">
            <?= $pesanNotifikasi ?>
            <button type="button" class="btn-close" onclick="this.parentElement.style.display='none';"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-md-4">
            <div class="bubble-card p-3">
                <h5 class="title-fun mb-3">📅 Pilih Tanggal Kelas:</h5>
                <div class="list-group list-group-fun">
                    <?php foreach ($kumpulanTanggal as $tgl): ?>
                        <a href="recap.php?view_date=<?= urlencode((string)$tgl['session_date']) ?>" class="list-group-item list-group-item-action <?= ($tgl['session_date'] === $tanggalTerpilih) ? 'active' : '' ?>">
                            📅 <?= date('d-m-Y', strtotime((string)$tgl['session_date'])) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="bubble-card p-4">
                <h4 class="title-fun text-dark mb-3">Pengajaran Tanggal: <span class="text-primary font-monospace"><?= date('d M Y', strtotime((string)$tanggalTerpilih)) ?></span></h4>
                
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

                <div class="row text-center mb-4 g-2">
                    <div class="col-6"><div class="p-3 bg-success bg-opacity-20 rounded-4 border border-dark fw-bold">🟢 Hadir: <?= $totalHadir ?> Anak</div></div>
                    <div class="col-6"><div class="p-3 bg-danger bg-opacity-20 rounded-4 border border-dark fw-bold">🔴 Absen: <?= $totalAbsen ?> Anak</div></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="table-dark">
                                <th>Nama Anak Terdata</th>
                                <th class="text-center">Status Kehadiran</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($barisData) === 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted fw-bold">Belum ada rekaman data pada tanggal ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($barisData as $b): ?>
                                    <?php $isHadir = json_decode((string)$b['checklist_json'], true)['attendance'] ?? false; ?>
                                    <tr>
                                        <td class="fw-bold text-capitalize text-dark"><?= proteksi((string)$b['child_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-status p-2 <?= $isHadir ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                                                <?= $isHadir ? '✅ MASUK KELAS' : '❌ TIDAK HADIR' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="recap.php?view_date=<?= urlencode((string)$tanggalTerpilih) ?>" onsubmit="return confirm('Apakah kamu yakin ingin menghapus absensi <?= var_export($b['child_name'], true) ?> pada tanggal ini?');" class="d-inline">
                                                <input type="hidden" name="action" value="hapus_absen">
                                                <input type="hidden" name="student_id" value="<?= (int)$b['student_id'] ?>">
                                                <input type="hidden" name="session_date" value="<?= proteksi((string)$tanggalTerpilih) ?>">
                                                
                                                <button type="submit" class="btn btn-hapus-fun">🗑️ Hapus Absen</button>
                                            </form>
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

</body>
</html>