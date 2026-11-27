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
    die('ID siswa tidak valid.');
}

// Ambil Data Profil & Nilai
$stmt = $pdo->prepare('
    SELECT s.*, g.* 
    FROM students s 
    LEFT JOIN student_grades g ON s.id = g.student_id 
    WHERE s.id = ?
');
$stmt->execute([$idSiswa]);
$data = $stmt->fetch();

if (!$data) {
    die('Data siswa tidak ditemukan!');
}

// Hitung Jumlah Kehadiran (mengabaikan status tidak hadir)
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

$nilaiBindo      = (int)($data['nilai_bindo'] ?? 0);
$nilaiPancasila  = (int)($data['nilai_pancasila'] ?? 0);
$nilaiMatematika = isset($data['nilai_matematika'])
    ? (int)$data['nilai_matematika']
    : (int)($data['nilai_seni'] ?? 0);
$nilaiIpas       = isset($data['nilai_ipas'])
    ? (int)$data['nilai_ipas']
    : (int)($data['nilai_ipa'] ?? ($data['nilai_ips'] ?? 0));
$nilaiBinggris   = (int)($data['nilai_binggris'] ?? 0);

$namaAnak      = (string)($data['child_name'] ?? '-');
$kelasSekolah  = (string)($data['school_class'] ?? '');
$umurAnak      = (int)($data['age'] ?? 0);
$minatBakat    = (string)($data['minat_bakat'] ?? '');
$evaluasiAnak  = (string)($data['evaluasi'] ?? '');

// Cek apakah ada permintaan download Excel
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $namaFileAman = preg_replace('/[^a-zA-Z0-9_-]/', '_', $namaAnak) ?: 'Siswa';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Rapot_' . $namaFileAman . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";

    echo '<table border="1">';
    echo '<tr><th colspan="3" style="background-color: #c1121f; color: white;">RAPOT PRESTASI ANAK - GSM IT-PLN</th></tr>';
    echo '<tr><td><b>Nama Anak</b></td><td colspan="2">' . htmlspecialchars($namaAnak, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    echo '<tr><td><b>Kelas Sekolah</b></td><td colspan="2">' . htmlspecialchars($kelasSekolah !== '' ? $kelasSekolah : '-', ENT_QUOTES, 'UTF-8') . '</td></tr>';
    echo '<tr><td><b>Umur</b></td><td colspan="2">' . ($umurAnak > 0 ? $umurAnak . ' Tahun' : '-') . '</td></tr>';
    echo '<tr><td><b>Kehadiran</b></td><td colspan="2">' . $totalHadir . ' Kali Masuk</td></tr>';
    echo '<tr><td colspan="3"></td></tr>';

    echo '<tr style="background-color: #780000; color: white;"><th>No</th><th>Mata Pelajaran</th><th>Nilai Angka</th></tr>';
    echo '<tr><td>1</td><td>Bahasa Indonesia</td><td>' . $nilaiBindo . '</td></tr>';
    echo '<tr><td>2</td><td>Pendidikan Pancasila</td><td>' . $nilaiPancasila . '</td></tr>';
    echo '<tr><td>3</td><td>Matematika</td><td>' . $nilaiMatematika . '</td></tr>';
    echo '<tr><td>4</td><td>IPAS</td><td>' . $nilaiIpas . '</td></tr>';
    echo '<tr><td>5</td><td>Bahasa Inggris</td><td>' . $nilaiBinggris . '</td></tr>';

    echo '<tr><td colspan="3"></td></tr>';
    echo '<tr><td><b>Catatan Minat & Bakat</b></td><td colspan="2">' . htmlspecialchars($minatBakat !== '' ? $minatBakat : '-', ENT_QUOTES, 'UTF-8') . '</td></tr>';
    echo '<tr><td><b>Evaluasi Perkembangan</b></td><td colspan="2">' . htmlspecialchars($evaluasiAnak !== '' ? $evaluasiAnak : '-', ENT_QUOTES, 'UTF-8') . '</td></tr>';
    echo '</table>';
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>RAPOT PRESTASI - <?= htmlspecialchars($namaAnak, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { 
            background: #e9ecef; 
            font-family: 'Nunito', sans-serif; 
            margin: 0; 
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #111827;
        }

        .action-bar {
            width: 210mm;
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .btn {
            padding: 8px 16px;
            font-weight: 800;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            border: 2px solid #1e293b;
            box-shadow: 2px 2px 0px #1e293b;
            cursor: pointer;
        }
        .btn-back { background-color: #64748b; }
        .btn-print { background-color: #0284c7; }
        .btn-excel { background-color: #16a34a; }
        .btn:active { transform: translate(2px, 2px); box-shadow: none; }
        
        .kertas-rapot {
            background-color: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 40px;
            border: 8px solid #c1121f;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            position: relative;
            box-sizing: border-box;
        }

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 4px double #1e293b;
            padding-bottom: 15px; 
            margin-bottom: 25px; 
        }
        .header .logo-kop { width: 85px; height: 85px; object-fit: contain; }
        .header .teks-kop { text-align: center; flex-grow: 1; padding: 0 15px; }
        .header h1 { color: #c1121f; margin: 0; font-size: 26px; font-weight: 900; text-transform: uppercase; }
        .header h3 { color: #1e293b; margin: 4px 0 0 0; font-size: 16px; font-weight: 800; text-transform: uppercase; }

        .profil-box { 
            border: 2px solid #1e293b; 
            border-left: 6px solid #c1121f;
            border-radius: 6px; 
            padding: 15px 20px; 
            background: #f8fafc; 
            margin-bottom: 25px; 
            font-size: 15px; 
        }
        .profil-box table { width: 100%; border-collapse: collapse; }
        .profil-box td { padding: 5px 8px; font-weight: 700; color: #0f172a; }

        .tabel-nilai { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 25px; 
            border: 2px solid #1e293b; 
            background: white;
        }
        .tabel-nilai th { 
            background-color: #780000;
            color: #ffffff; 
            border: 2px solid #1e293b; 
            padding: 12px; 
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .tabel-nilai td { 
            border: 2px solid #1e293b; 
            padding: 10px 12px; 
            text-align: center; 
            font-weight: 700; 
            font-size: 15px;
        }

        .catatan-section { margin-bottom: 20px; }
        .catatan-title {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            background-color: #1e293b;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 4px 4px 0 0;
            text-transform: uppercase;
        }
        .catatan-body {
            border: 2px solid #1e293b;
            border-radius: 0 6px 6px 6px;
            padding: 12px 15px;
            background: #ffffff;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            min-height: 50px;
        }

        .footer-ttd { 
            margin-top: 45px; 
            display: flex; 
            justify-content: flex-end; 
            font-weight: 800; 
            text-align: center; 
            font-size: 15px;
        }
        .ttd-line { border-top: 2px solid #0f172a; width: 200px; margin-top: 80px; }

        @media print {
            body { background: none; padding: 0; display: block; }
            .action-bar { display: none !important; }
            .kertas-rapot { 
                box-shadow: none; 
                border-radius: 0; 
                width: 100%; 
                border: 8px solid #c1121f !important;
            }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <div>
            <a href="rapot.php" class="btn btn-back">⬅️ Kembali</a>
        </div>
        <div>
            <a href="?id=<?= $idSiswa ?>&action=export_excel" class="btn btn-excel">📥 Download Excel</a>
            <button type="button" onclick="window.print()" class="btn btn-print">🖨️ Cetak / Print PDF</button>
        </div>
    </div>

    <div class="kertas-rapot">
        
        <div class="header">
            <img src="Logo GSM.PNG" alt="Logo GSM" class="logo-kop">
            
            <div class="teks-kop">
                <h1>RAPOT PRESTASI ANAK</h1>
                <h3>Gerakan Sahabat Mengajar IT-PLN</h3>
            </div>
            
            <img src="Logo-Kabinet.png" alt="Logo Kabinet" class="logo-kop"> 
        </div>

        <div class="profil-box">
            <table>
                <tr>
                    <td width="18%">Nama Anak</td>
                    <td width="2%">:</td>
                    <td width="35%"><?= htmlspecialchars($namaAnak, ENT_QUOTES, 'UTF-8') ?></td>
                    <td width="18%">Kehadiran</td>
                    <td width="2%">:</td>
                    <td width="25%"><strong style="color: #c1121f;"><?= $totalHadir ?> Kali Masuk</strong></td>
                </tr>
                <tr>
                    <td>Kelas Sekolah</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($kelasSekolah !== '' ? $kelasSekolah : '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>Umur</td>
                    <td>:</td>
                    <td><?= $umurAnak > 0 ? $umurAnak . ' Tahun' : '-' ?></td>
                </tr>
            </table>
        </div>

        <table class="tabel-nilai">
            <thead>
                <tr>
                    <th width="12%">No</th>
                    <th width="58%">Mata Pelajaran</th>
                    <th width="30%">Nilai Angka</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td style="text-align: left;">Bahasa Indonesia</td>
                    <td><strong><?= $nilaiBindo ?></strong></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td style="text-align: left;">Pendidikan Pancasila</td>
                    <td><strong><?= $nilaiPancasila ?></strong></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td style="text-align: left;">Matematika</td>
                    <td><strong><?= $nilaiMatematika ?></strong></td>
                </tr>
                <tr>
                    <td>4</td>
                    <td style="text-align: left;">IPAS</td>
                    <td><strong><?= $nilaiIpas ?></strong></td>
                </tr>
                <tr>
                    <td>5</td>
                    <td style="text-align: left;">Bahasa Inggris</td>
                    <td><strong><?= $nilaiBinggris ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="catatan-section">
            <div class="catatan-title">🎨 Catatan Minat & Bakat</div>
            <div class="catatan-body">
                <?= nl2br(htmlspecialchars($minatBakat !== '' ? $minatBakat : 'Belum ada catatan minat & bakat.', ENT_QUOTES, 'UTF-8')) ?>
            </div>
        </div>

        <div class="catatan-section">
            <div class="catatan-title" style="background-color: #780000;">💡 Evaluasi Perkembangan Anak</div>
            <div class="catatan-body">
                <?= nl2br(htmlspecialchars($evaluasiAnak !== '' ? $evaluasiAnak : 'Belum ada catatan evaluasi.', ENT_QUOTES, 'UTF-8')) ?>
            </div>
        </div>

        <div class="footer-ttd">
            <div>
                <p>Jakarta, <?= date('d F Y') ?></p>
                <div class="ttd-line">Pengajar GSM</div>
            </div>
        </div>

    </div>
</body>
</html>