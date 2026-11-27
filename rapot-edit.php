<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['is_admin_logged_in']) && empty($_SESSION['akses_edit_gsm'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/koneksi.php';

$idSiswa = (int)($_GET['id'] ?? 0);
if ($idSiswa <= 0) {
    header('Location: rapot.php');
    exit;
}

$pesan = '';
$errorPesan = '';

$stmt = $pdo->prepare('SELECT child_name, school_class, age FROM students WHERE id = ?');
$stmt->execute([$idSiswa]);
$anak = $stmt->fetch();
if (!$anak) {
    die('Data anak tidak ditemukan.');
}

$stmtAbsen = $pdo->prepare('SELECT checklist_json FROM attendance_records WHERE student_id = ?');
$stmtAbsen->execute([$idSiswa]);
$absenRecords = $stmtAbsen->fetchAll();
$totalHadir = 0;
foreach ($absenRecords as $rec) {
    $json = json_decode((string)$rec['checklist_json'], true);
    $isHadir = is_array($json)
        ? (array_key_exists('attendance', $json) ? (bool)$json['attendance'] : true)
        : true;
    if ($isHadir) {
        $totalHadir++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bindo      = (int)($_POST['nilai_bindo'] ?? 0);
    $pancasila  = (int)($_POST['nilai_pancasila'] ?? 0);
    $matematika = (int)($_POST['nilai_matematika'] ?? 0);
    $ipas       = (int)($_POST['nilai_ipas'] ?? 0);
    $binggris   = (int)($_POST['nilai_binggris'] ?? 0);
    $minat      = trim((string)($_POST['minat_bakat'] ?? ''));
    $evaluasi   = trim((string)($_POST['evaluasi'] ?? ''));

    try {
        $sql = "INSERT INTO student_grades (
                    student_id, nilai_bindo, nilai_pancasila, nilai_matematika, nilai_ipas, nilai_binggris, minat_bakat, evaluasi
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    nilai_bindo = VALUES(nilai_bindo),
                    nilai_pancasila = VALUES(nilai_pancasila),
                    nilai_matematika = VALUES(nilai_matematika),
                    nilai_ipas = VALUES(nilai_ipas),
                    nilai_binggris = VALUES(nilai_binggris),
                    minat_bakat = VALUES(minat_bakat),
                    evaluasi = VALUES(evaluasi)";

        $simpan = $pdo->prepare($sql);
        $simpan->execute([$idSiswa, $bindo, $pancasila, $matematika, $ipas, $binggris, $minat, $evaluasi]);
        $pesan = '🎉 Berhasil menyimpan nilai rapot!';
    } catch (Throwable $e) {
        $errorPesan = '🚫 Gagal menyimpan nilai rapot: ' . $e->getMessage();
    }
}

$stmtGrade = $pdo->prepare('SELECT * FROM student_grades WHERE student_id = ?');
$stmtGrade->execute([$idSiswa]);
$nilai = $stmtGrade->fetch() ?: [
    'nilai_bindo'      => 0,
    'nilai_pancasila'  => 0,
    'nilai_matematika' => 0,
    'nilai_ipas'       => 0,
    'nilai_binggris'   => 0,
    'minat_bakat'      => '',
    'evaluasi'         => '',
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Input Nilai Rapot - <?= htmlspecialchars((string)$anak['child_name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Nunito', sans-serif; }
        .bubble-card { background: #fff; border: 3px solid #1e293b; border-radius: 20px; box-shadow: 5px 5px 0px #1e293b; padding: 25px; }
        .form-control { border: 2.5px solid #1e293b; border-radius: 10px; font-weight: bold; }
        .form-control:focus { border-color: #ff006e; box-shadow: none; }
    </style>
</head>
<body class="p-3 p-md-4">
    <div class="container" style="max-width: 800px;">
        <div class="bubble-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="m-0" style="font-family:'Fredoka';">📝 Edit Rapot Anak</h3>
                <span class="badge bg-primary fs-6 border border-dark">🏃‍♂️ <?= $totalHadir ?> Kali Masuk</span>
            </div>
            <p class="text-muted fw-bold mb-3">Siswa: <u><?= htmlspecialchars((string)$anak['child_name'], ENT_QUOTES, 'UTF-8') ?></u> (Kelas: <?= htmlspecialchars((string)($anak['school_class'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>)</p>
            <hr class="border-2 border-dark">

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-success border border-dark fw-bold mb-3"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($errorPesan !== ''): ?>
                <div class="alert alert-danger border border-dark fw-bold mb-3"><?= htmlspecialchars($errorPesan, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST">
                <h5 class="fw-bold mb-3" style="font-family:'Fredoka';">📊 Nilai Mata Pelajaran</h5>
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold">📖 B. Indo</label>
                        <input type="number" name="nilai_bindo" class="form-control text-center fs-5" value="<?= (int)($nilai['nilai_bindo'] ?? 0) ?>" min="0" max="100">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold">🦅 Pancasila</label>
                        <input type="number" name="nilai_pancasila" class="form-control text-center fs-5" value="<?= (int)($nilai['nilai_pancasila'] ?? 0) ?>" min="0" max="100">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-bold">🔢 Matematika</label>
                        <input type="number" name="nilai_matematika" class="form-control text-center fs-5" value="<?= (int)($nilai['nilai_matematika'] ?? 0) ?>" min="0" max="100">
                    </div>
                    <div class="col-6 col-md-6">
                        <label class="form-label fw-bold">🔬🌍 IPAS</label>
                        <input type="number" name="nilai_ipas" class="form-control text-center fs-5" value="<?= (int)($nilai['nilai_ipas'] ?? 0) ?>" min="0" max="100">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">🇬🇧 B. Inggris</label>
                        <input type="number" name="nilai_binggris" class="form-control text-center fs-5" value="<?= (int)($nilai['nilai_binggris'] ?? 0) ?>" min="0" max="100">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">🎨 Catatan Minat & Bakat</label>
                    <textarea name="minat_bakat" class="form-control" rows="3" placeholder="Contoh: Sangat menonjol di bidang sains dan logika..."><?= htmlspecialchars((string)($nilai['minat_bakat'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">💡 Evaluasi Perkembangan Anak</label>
                    <textarea name="evaluasi" class="form-control" rows="3" placeholder="Contoh: Perlu pembiasaan lebih pada ketelitian menghitung..."><?= htmlspecialchars((string)($nilai['evaluasi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <a href="rapot.php" class="btn btn-secondary border-2 border-dark fw-bold px-3">⬅️ Kembali</a>
                    <button type="submit" class="btn text-white border-2 border-dark fw-bold px-4" style="background-color: #ff006e;">💾 Simpan Rapot</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>