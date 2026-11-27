<?php
declare(strict_types=1);
session_start();

// === PROTEKSI LOGIN ADMIN / AKSES EDIT ===
if (empty($_SESSION['is_admin_logged_in']) && empty($_SESSION['akses_edit_gsm'])) {
    header('Location: login.php?redirect=' . urlencode('rapot.php'));
    exit;
}

require_once __DIR__ . '/koneksi.php';

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// === FITUR DOWNLOAD EXCEL SEMUA SISWA ===
if (isset($_GET['action']) && $_GET['action'] === 'export_all_excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Rekap_Semua_Rapot_Siswa_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";

    $stmtAll = $pdo->query('
        SELECT s.*, g.* 
        FROM students s 
        LEFT JOIN student_grades g ON s.id = g.student_id 
        ORDER BY s.child_name ASC
    ');
    $semuaSiswaExcel = $stmtAll->fetchAll();

    // Hitung kehadiran untuk excel (mengabaikan status tidak hadir)
    $queryAbsenExcel = $pdo->query('SELECT student_id, checklist_json FROM attendance_records');
    $semuaAbsenExcel = $queryAbsenExcel->fetchAll();
    $hitungHadirExcel = [];
    foreach ($semuaAbsenExcel as $absen) {
        $idS = (int)$absen['student_id'];
        $json = json_decode((string)$absen['checklist_json'], true);
        if (!isset($hitungHadirExcel[$idS])) {
            $hitungHadirExcel[$idS] = 0;
        }
        $isHadir = is_array($json)
            ? (array_key_exists('attendance', $json) ? (bool)$json['attendance'] : true)
            : true;
        if ($isHadir) {
            $hitungHadirExcel[$idS]++;
        }
    }

    echo '<table border="1">';
    echo '<tr style="background-color: #1e293b; color: white; font-weight: bold;">
            <th>No</th>
            <th>Nama Anak</th>
            <th>Kelas</th>
            <th>Umur</th>
            <th>Kehadiran</th>
            <th>B. Indonesia</th>
            <th>Pendidikan Pancasila</th>
            <th>Matematika</th>
            <th>IPAS</th>
            <th>B. Inggris</th>
            <th>Catatan Minat & Bakat</th>
            <th>Evaluasi Perkembangan</th>
          </tr>';

    $no = 1;
    foreach ($semuaSiswaExcel as $s) {
        $idS = (int)$s['id'];
        $totalHadir = $hitungHadirExcel[$idS] ?? 0;

        $nilaiBindo = (int)($s['nilai_bindo'] ?? 0);
        $nilaiPancasila = (int)($s['nilai_pancasila'] ?? 0);
        $nilaiMatematika = isset($s['nilai_matematika'])
            ? (int)$s['nilai_matematika']
            : (int)($s['nilai_seni'] ?? 0);
        $nilaiIpas = isset($s['nilai_ipas'])
            ? (int)$s['nilai_ipas']
            : (int)($s['nilai_ipa'] ?? ($s['nilai_ips'] ?? 0));
        $nilaiBinggris = (int)($s['nilai_binggris'] ?? 0);

        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . proteksi((string)($s['child_name'] ?? '-')) . '</td>';
        echo '<td>' . proteksi(!empty($s['school_class']) ? (string)$s['school_class'] : '-') . '</td>';
        echo '<td>' . (((int)($s['age'] ?? 0)) > 0 ? (int)$s['age'] . ' Thn' : '-') . '</td>';
        echo '<td>' . $totalHadir . ' Kali Masuk</td>';
        echo '<td>' . $nilaiBindo . '</td>';
        echo '<td>' . $nilaiPancasila . '</td>';
        echo '<td>' . $nilaiMatematika . '</td>';
        echo '<td>' . $nilaiIpas . '</td>';
        echo '<td>' . $nilaiBinggris . '</td>';
        echo '<td>' . proteksi((string)($s['minat_bakat'] ?? '-')) . '</td>';
        echo '<td>' . proteksi((string)($s['evaluasi'] ?? '-')) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

// Ambil data siswa & nilai untuk tampilan tabel web
$query = $pdo->query('
    SELECT s.id, s.child_name, s.school_class, s.age,
           (CASE WHEN g.student_id IS NOT NULL THEN 1 ELSE 0 END) as status_nilai
    FROM students s 
    LEFT JOIN student_grades g ON s.id = g.student_id 
    ORDER BY s.child_name ASC
');
$semuaSiswa = $query->fetchAll();

// Hitung Kehadiran dari JSON Absensi (mengabaikan status tidak hadir)
$queryAbsen = $pdo->query('SELECT student_id, checklist_json FROM attendance_records');
$semuaAbsen = $queryAbsen->fetchAll();

$hitungHadir = [];
foreach ($semuaAbsen as $absen) {
    $idS = (int)$absen['student_id'];
    $json = json_decode((string)$absen['checklist_json'], true);
    if (!isset($hitungHadir[$idS])) {
        $hitungHadir[$idS] = 0;
    }
    $isHadir = is_array($json)
        ? (array_key_exists('attendance', $json) ? (bool)$json['attendance'] : true)
        : true;
    if ($isHadir) {
        $hitungHadir[$idS]++;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Rapot & Ujian - GSMConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f3fbf7; font-family: 'Nunito', sans-serif; }
        .navbar-fun { background-color: #ffffff; border-bottom: 4px solid #1e293b; box-shadow: 0 4px 0px #1e293b; }
        .bubble-card { background: #ffffff; border: 3px solid #1e293b; border-radius: 20px; box-shadow: 4px 4px 0px #1e293b; }
        .btn-fun { font-family: 'Fredoka'; font-weight: 700; border: 2px solid #1e293b; border-radius: 10px; box-shadow: 2px 2px 0px #1e293b; transition: all 0.15s ease; text-decoration: none; padding: 6px 14px; display: inline-flex; align-items: center; gap: 4px; }
        .btn-fun:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0px #1e293b; }
        .btn-fun:active { transform: scale(0.96); }
        .btn-edit { background-color: #ffbe0b; color: #1e293b; }
        .btn-print { background-color: #06d6a0; color: #fff; }
        .btn-excel { background-color: #2ec4b6; color: #fff; }
        .badge-hadir { background-color: #3a86ff; color: white; border: 2px solid #1e293b; font-weight: 800; }
    </style>
</head>
<body>
<nav class="navbar navbar-fun py-3 mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <h3 class="m-0" style="font-family:'Fredoka';">🎓 Rapot & Hasil Ujian Anak</h3>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn-fun bg-light text-dark">🏠 Menu Utama</a>
            <a href="logout.php" class="btn-fun bg-danger text-white">🔒 Logout Admin</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold m-0" style="font-family:'Fredoka';">Daftar Rekap Nilai Siswa</h5>
        <a href="?action=export_all_excel" class="btn-fun btn-excel">
            📥 Download Semua Rekap Excel (.xls)
        </a>
    </div>

    <div class="bubble-card p-3 p-md-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Profil Anak</th>
                        <th>Kelas</th>
                        <th class="text-center">Kehadiran</th>
                        <th class="text-center">Status Rapot</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($semuaSiswa) === 0): ?>
                        <tr><td colspan="5" class="text-center py-4">Belum ada data anak.</td></tr>
                    <?php else: ?>
                        <?php foreach ($semuaSiswa as $siswa): 
                            $idS = (int)$siswa['id'];
                            $totalHadir = $hitungHadir[$idS] ?? 0;
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold fs-6"><?= proteksi((string)$siswa['child_name']) ?></div>
                                    <small class="text-muted">Umur: <?= ((int)$siswa['age']) > 0 ? (int)$siswa['age'] . ' Thn' : '-' ?></small>
                                </td>
                                <td class="fw-bold"><?= proteksi(!empty($siswa['school_class']) ? (string)$siswa['school_class'] : '-') ?></td>
                                <td class="text-center">
                                    <span class="badge badge-hadir px-2.5 py-1.5 rounded-pill">
                                        🏃‍♂️ <?= $totalHadir ?> Kali Masuk
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($siswa['status_nilai']): ?>
                                        <span class="badge bg-success border border-dark rounded-pill">✅ Nilai Terisi</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark border border-dark rounded-pill">⚠️ Belum Diisi</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="rapot-edit.php?id=<?= $idS ?>" class="btn-fun btn-edit">📝 Input/Edit</a>
                                        <a href="rapot-cetak.php?id=<?= $idS ?>" target="_blank" class="btn-fun btn-print">🖨️ Cetak Rapot</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>