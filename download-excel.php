<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/koneksi.php';

try {
    // Ambil data siswa
    $query = $pdo->query('SELECT id, child_name, age, school_class, phone_number FROM students ORDER BY child_name ASC');
    $semuaSiswa = $query->fetchAll();

    // Ambil data absensi untuk menghitung kehadiran
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
} catch (Throwable $e) {
    die('Gagal memproses data Excel: ' . $e->getMessage());
}

// Memicu browser agar langsung mendownload file sebagai Excel (.xls)
header("Content-Type: application/vnd-ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Recap_Anak_GSM_" . date('Y-m-d') . ".xls");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        .text-center { text-align: center; }
        th { background-color: #3a86ff; color: white; font-weight: bold; }
    </style>
</head>
<body>

    <h2>REKAP DATA ANAK AKTIF & TOTAL KEHADIRAN</h2>
    <h3>Gerakan Sahabat Mengajar IT-PLN</h3>
    <p>Tanggal Unduh: <?php echo date('d-m-Y H:i'); ?></p>

    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Lengkap Anak</th>
                <th>Umur (Tahun)</th>
                <th>Kelas Sekolah</th>
                <th>No. HP Orang Tua</th>
                <th>Total Kehadiran (Sesi)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($semuaSiswa) === 0): ?>
                <tr>
                    <td colspan="6" class="text-center">Belum ada data anak.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($semuaSiswa as $siswa): ?>
                    <?php 
                        $idSiswa = (int)$siswa['id'];
                        $jumlahKehadiran = $hitungHadir[$idSiswa] ?? 0;
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td><?= htmlspecialchars((string)$siswa['child_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center"><?= $siswa['age'] > 0 ? $siswa['age'] : '-' ?></td>
                        <td><?= htmlspecialchars((string)$siswa['school_class'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>'<?= htmlspecialchars((string)$siswa['phone_number'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center"><?= $jumlahKehadiran ?> Kali</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>