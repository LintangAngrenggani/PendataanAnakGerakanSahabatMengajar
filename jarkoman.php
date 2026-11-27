<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/koneksi.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

const JARKOMAN_USER = 'koneksi';
const JARKOMAN_PASS = 'koneksioke';

function proteksi(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatTanggalIndo(string $tanggalIso): string {
    $ts = strtotime($tanggalIso);
    if ($ts === false) {
        return $tanggalIso;
    }
    $hariMap = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];
    $bulanMap = [
        'January'   => 'Januari',
        'February'  => 'Februari',
        'March'     => 'Maret',
        'April'     => 'April',
        'May'       => 'Mei',
        'June'      => 'Juni',
        'July'      => 'Juli',
        'August'    => 'Agustus',
        'September' => 'September',
        'October'   => 'Oktober',
        'November'  => 'November',
        'December'  => 'Desember',
    ];
    $hariEng = date('l', $ts);
    $bulanEng = date('F', $ts);
    $hariIndo = $hariMap[$hariEng] ?? $hariEng;
    $bulanIndo = $bulanMap[$bulanEng] ?? $bulanEng;

    return $hariIndo . ', ' . date('d', $ts) . ' ' . $bulanIndo . ' ' . date('Y', $ts);
}

function cariFilePosterSrengseng(string $jenis, string $baseUrl): array {
    $kandidat = ($jenis === 'libur_srengseng')
        ? ['LIBUR LES.PNG', 'libur-les.png', 'LIBUR LES.png', 'libur les.png']
        : ['MASUK LES.PNG', 'masuk-les.png', 'MASUK LES.png', 'masuk les.png'];

    $folderCek = [
        ['dir' => __DIR__ . '/uploads/jarkoman', 'urlPrefix' => $baseUrl . '/uploads/jarkoman/'],
        ['dir' => __DIR__, 'urlPrefix' => $baseUrl . '/'],
    ];

    foreach ($folderCek as $lok) {
        foreach ($kandidat as $namaFile) {
            $fullPath = $lok['dir'] . '/' . $namaFile;
            if (is_file($fullPath)) {
                return [
                    'nama_file' => $namaFile,
                    'url'       => $lok['urlPrefix'] . rawurlencode($namaFile),
                ];
            }
        }
    }

    return [
        'nama_file' => '',
        'url'       => '',
    ];
}

function susunPesanJarkoman(array $cfg, string $hariKe): string {
    $jenis = (string)($cfg['jenis_jarkoman'] ?? 'masuk_pengajaran');
    $grup  = (string)($cfg['target_grup'] ?? 'superstar_kabinet');
    $tglAcaraFmt = formatTanggalIndo((string)$cfg['tanggal_acara']);
    $tempat = trim((string)($cfg['tempat'] ?? 'Hutan Kota Srengseng'));
    $kegiatan = trim((string)($cfg['kegiatan'] ?? 'Kegiatan Les'));
    $jamKumpul = trim((string)($cfg['jam_kumpul'] ?? '07:30 - 08:00'));
    $jamBerangkat = trim((string)($cfg['jam_berangkat'] ?? '08:00'));
    $jamPengajaran = trim((string)($cfg['jam_pengajaran'] ?? '09:00 - Selesai'));
    $tglKembaliFmt = !empty($cfg['tanggal_masuk_kembali']) ? formatTanggalIndo((string)$cfg['tanggal_masuk_kembali']) : $tglAcaraFmt;
    $alasan = trim((string)($cfg['alasan_libur'] ?? 'ujian sekolah'));

    $prefixPengingat = match ($hariKe) {
        'H-2'   => "🔔 *PENGINGAT H-2*\n\n",
        'H-1'   => "⏰ *PENGINGAT H-1 (BESOK)*\n\n",
        default => "📢 *HARI INI*\n\n",
    };

    if ($jenis === 'libur_srengseng') {
        return $prefixPengingat
            . "Halo Adik-adik! Ada pengumuman penting, nih.\n\n"
            . "Untuk sementara waktu, kegiatan les kita libur dulu yaa. Soalnya, kakak abang pengajar lagi mau " . $alasan . " dulu nih, doakan lancar ya!\n\n"
            . "Tapi tenang aja, kita bakal belajar dan seru-seruan lagi pada hari " . $tglKembaliFmt . ".\n\n"
            . "Jangan lupa datang lagi di tanggal itu ya, Adik-adik! Sampai jumpa lagi dan tetap semangat!";
    }

    if ($grup === 'anak_srengseng') {
        return $prefixPengingat
            . "Assalamualaikum wr.wb.,\n\n"
            . "Berhubungan dengan kegiatan Les yang diadakan setiap satu kali pertemuan dalam satu Minggu. Kegiatan Les akan kembali dilaksanakan pada :\n"
            . "🗓️ : " . $tglAcaraFmt . "\n"
            . "🏫 : " . $tempat . "\n"
            . "📌 : " . $kegiatan . "\n"
            . "🕒 : " . $jamPengajaran . "\n\n"
            . "Diharapkan Adik-adik dapat datang untuk mengikuti kegiatan Les ini. Terimakasih atas perhatiannya. 🙏\n\n"
            . "Wassalamualaikum wr.wb";
    }

    return $prefixPengingat
        . "Assalamualaikum Wr. Wb.,\n\n"
        . "Berhubungan dengan kegiatan pengajaran yang diadakan setiap seminggu. Maka dari itu, kami mengundang dan mengajak Abang, Kakak dan Teman-Teman untuk mengikuti kegiatan ini yang akan kembali dilaksanakan pada :\n"
        . "🗓️ : " . $tglAcaraFmt . "\n"
        . "🏫 : " . $tempat . "\n"
        . "📌 : " . $kegiatan . "\n"
        . "🕒\n"
        . "Kumpul : " . $jamKumpul . "\n"
        . "Berangkat : " . $jamBerangkat . "\n"
        . "Pengajaran : " . $jamPengajaran . "\n\n"
        . "Kami mengharapkan kehadiran Abang, Kakak dan Teman-Teman di kegiatan ini.\n"
        . "Terimakasih atas perhatiannya.🙏\n\n"
        . "Wassalamualaikum Wr. Wb.";
}

function buatLinkWa(string $pesan): string {
    return 'https://wa.me/?text=' . rawurlencode($pesan);
}

$pesanNotif = '';
$tipeNotif = 'success';
$bukaModalAuth = false;

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$baseDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/jarkoman.php'))), '/');
$baseUrl = $scheme . '://' . $host . ($baseDir !== '' && $baseDir !== '.' ? $baseDir : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $token)) {
        $pesanNotif = 'Sesi habis, silakan muat ulang halaman.';
        $tipeNotif = 'danger';
    } else {
        $aksi = (string)($_POST['aksi'] ?? '');

        if ($aksi === 'login_jarkoman') {
            $usn = trim((string)($_POST['username'] ?? ''));
            $pw  = (string)($_POST['password'] ?? '');
            if (hash_equals(JARKOMAN_USER, $usn) && hash_equals(JARKOMAN_PASS, $pw)) {
                $_SESSION['akses_edit_gsm'] = true;
                $pesanNotif = 'Akses kirim & kelola jarkoman dibuka!';
                $tipeNotif = 'success';
            } else {
                $pesanNotif = 'Username atau password salah!';
                $tipeNotif = 'danger';
                $bukaModalAuth = true;
            }
        } elseif ($aksi === 'logout_jarkoman') {
            unset($_SESSION['akses_edit_gsm']);
            $pesanNotif = 'Akses kirim & kelola jarkoman dikunci kembali.';
            $tipeNotif = 'warning';
        } elseif (empty($_SESSION['akses_edit_gsm']) && empty($_SESSION['is_admin_logged_in'])) {
            $pesanNotif = 'Masukkan username dan password terlebih dahulu untuk mengirim atau menyimpan jarkoman.';
            $tipeNotif = 'danger';
            $bukaModalAuth = true;
        } else {
            if ($aksi === 'buat_jadwal_jarkoman') {
                $jenisJarkoman = (string)($_POST['jenis_jarkoman'] ?? 'masuk_pengajaran');
                if (!in_array($jenisJarkoman, ['masuk_pengajaran', 'libur_srengseng'], true)) {
                    $jenisJarkoman = 'masuk_pengajaran';
                }

                $targetGrup = ($jenisJarkoman === 'libur_srengseng')
                    ? 'anak_srengseng'
                    : (string)($_POST['target_grup'] ?? 'superstar_kabinet');
                if (!in_array($targetGrup, ['superstar_kabinet', 'anak_srengseng'], true)) {
                    $targetGrup = 'superstar_kabinet';
                }

                $tanggalAcara = trim((string)($_POST['tanggal_acara'] ?? date('Y-m-d')));
                $tanggalKembali = trim((string)($_POST['tanggal_masuk_kembali'] ?? ''));
                $alasanLibur = trim((string)($_POST['alasan_libur'] ?? 'ujian sekolah'));
                $tempat = trim((string)($_POST['tempat'] ?? 'Hutan Kota Srengseng'));
                $kegiatan = trim((string)($_POST['kegiatan'] ?? 'Kegiatan Les'));
                $jamKumpul = trim((string)($_POST['jam_kumpul'] ?? '07:30 - 08:00'));
                $jamBerangkat = trim((string)($_POST['jam_berangkat'] ?? '08:00'));
                $jamPengajaran = trim((string)($_POST['jam_pengajaran'] ?? '09:00 - Selesai'));

                $namaFileGambar = null;
                if ($targetGrup === 'anak_srengseng' || $jenisJarkoman === 'libur_srengseng') {
                    if (isset($_FILES['gambar_jarkoman']) && (int)($_FILES['gambar_jarkoman']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo((string)$_FILES['gambar_jarkoman']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                            $folderUpload = __DIR__ . '/uploads/jarkoman';
                            if (!is_dir($folderUpload)) {
                                mkdir($folderUpload, 0777, true);
                            }
                            $namaFileGambar = 'jarkoman_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                            move_uploaded_file((string)$_FILES['gambar_jarkoman']['tmp_name'], $folderUpload . '/' . $namaFileGambar);
                        } else {
                            $pesanNotif = 'Format gambar harus JPG, PNG, atau WEBP.';
                            $tipeNotif = 'warning';
                        }
                    }
                    if ($namaFileGambar === null) {
                        $infoPoster = cariFilePosterSrengseng($jenisJarkoman, $baseUrl);
                        $namaFileGambar = $infoPoster['nama_file'] !== '' ? $infoPoster['nama_file'] : null;
                    }
                }

                if ($pesanNotif === '') {
                    $tsAcara = strtotime($tanggalAcara) ?: time();
                    $jadwalHari = [
                        'H-2'    => date('Y-m-d', strtotime('-2 days', $tsAcara)),
                        'H-1'    => date('Y-m-d', strtotime('-1 day', $tsAcara)),
                        'Hari H' => date('Y-m-d', $tsAcara),
                    ];

                    $cfg = [
                        'jenis_jarkoman'        => $jenisJarkoman,
                        'target_grup'           => $targetGrup,
                        'tanggal_acara'         => $tanggalAcara,
                        'tanggal_masuk_kembali' => $tanggalKembali !== '' ? $tanggalKembali : null,
                        'alasan_libur'          => $alasanLibur !== '' ? $alasanLibur : 'ujian sekolah',
                        'tempat'                => $tempat !== '' ? $tempat : 'Hutan Kota Srengseng',
                        'kegiatan'              => $kegiatan !== '' ? $kegiatan : 'Kegiatan Les',
                        'jam_kumpul'            => $jamKumpul,
                        'jam_berangkat'         => $jamBerangkat,
                        'jam_pengajaran'        => $jamPengajaran,
                    ];

                    $stmtIns = $pdo->prepare('
                        INSERT INTO jarkoman_schedules (
                            jenis_jarkoman, target_grup, tanggal_acara, tanggal_masuk_kembali, alasan_libur,
                            tempat, kegiatan, jam_kumpul, jam_berangkat, jam_pengajaran,
                            gambar_path, link_grup_wa, hari_ke, tanggal_kirim, status_kirim, pesan_teks
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');

                    foreach ($jadwalHari as $labelHari => $tglKirim) {
                        $pesanJadi = susunPesanJarkoman($cfg, $labelHari);
                        $statusAwal = ($tglKirim <= date('Y-m-d')) ? 'terkirim' : 'menunggu';
                        $stmtIns->execute([
                            $jenisJarkoman,
                            $targetGrup,
                            $tanggalAcara,
                            $tanggalKembali !== '' ? $tanggalKembali : null,
                            $cfg['alasan_libur'],
                            $cfg['tempat'],
                            $cfg['kegiatan'],
                            $jamKumpul,
                            $jamBerangkat,
                            $jamPengajaran,
                            $namaFileGambar,
                            '',
                            $labelHari,
                            $tglKirim,
                            $statusAwal,
                            $pesanJadi,
                        ]);
                    }

                    $pesanNotif = 'Jadwal jarkoman otomatis untuk H-2, H-1, dan Hari H berhasil dibuat!';
                    $tipeNotif = 'success';
                }
            } elseif ($aksi === 'tandai_terkirim') {
                $idJadwal = (int)($_POST['id'] ?? 0);
                if ($idJadwal > 0) {
                    $stmtUpd = $pdo->prepare('UPDATE jarkoman_schedules SET status_kirim = "terkirim" WHERE id = ?');
                    $stmtUpd->execute([$idJadwal]);
                    $pesanNotif = 'Status jarkoman diperbarui menjadi Terkirim.';
                    $tipeNotif = 'success';
                }
            } elseif ($aksi === 'hapus_jadwal') {
                $idJadwal = (int)($_POST['id'] ?? 0);
                if ($idJadwal > 0) {
                    $stmtDel = $pdo->prepare('DELETE FROM jarkoman_schedules WHERE id = ?');
                    $stmtDel->execute([$idJadwal]);
                    $pesanNotif = 'Jadwal jarkoman berhasil dihapus.';
                    $tipeNotif = 'warning';
                }
            }
        }
    }
}

$punyaAksesKirim = !empty($_SESSION['akses_edit_gsm']) || !empty($_SESSION['is_admin_logged_in']);

$daftarJadwal = [];
try {
    $daftarJadwal = $pdo->query('SELECT * FROM jarkoman_schedules ORDER BY tanggal_kirim ASC, id DESC')->fetchAll();
} catch (Throwable $e) {
    $daftarJadwal = [];
}

$hariIni = date('Y-m-d');
$posterMasuk = cariFilePosterSrengseng('masuk_pengajaran', $baseUrl);
$posterLibur = cariFilePosterSrengseng('libur_srengseng', $baseUrl);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jarkoman Pengajaran - GSMConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f3fbf7; color: #1e293b; font-family: 'Nunito', sans-serif; }
        h1, h2, h3, h4, h5, .brand-title { font-family: 'Fredoka', sans-serif; font-weight: 700; }
        .navbar-fun { background-color: #ffffff; border-bottom: 4px solid #1e293b; box-shadow: 0 4px 0px #1e293b; }
        .bubble-card { background: #ffffff; border: 3px solid #1e293b; border-radius: 20px; box-shadow: 4px 4px 0px #1e293b; }
        .form-control, .form-select { border: 2.5px solid #1e293b; border-radius: 12px; font-weight: 700; }
        .form-control:focus, .form-select:focus { border-color: #ff006e; box-shadow: none; }
        .btn-fun { font-family: 'Fredoka'; font-weight: 700; border: 2.5px solid #1e293b; border-radius: 12px; box-shadow: 3px 3px 0px #1e293b; text-decoration: none; transition: all 0.12s ease; }
        .btn-fun:hover { transform: translate(-1px, -1px); box-shadow: 4px 4px 0px #1e293b; }
        .preview-box { background: #eefbf3; border: 2.5px dashed #1e293b; border-radius: 14px; padding: 14px; white-space: pre-line; font-size: 0.9rem; font-weight: 700; }
        .poster-preview-img { max-height: 220px; border: 2.5px solid #1e293b; border-radius: 12px; object-fit: contain; background: #fff; }
    </style>
</head>
<body>
<nav class="navbar navbar-fun py-3 mb-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fs-4 fs-md-3 brand-title text-dark m-0">📣 Jarkoman Pengajaran GSM</span>
        <div class="d-flex gap-2">
            <?php if ($punyaAksesKirim): ?>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= proteksi((string)$_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="aksi" value="logout_jarkoman">
                    <button type="submit" class="btn btn-fun bg-info text-dark px-3 py-2">🔓 Kunci Akses</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn btn-fun bg-info text-dark px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAuthJarkoman">
                    🔐 Login Kirim
                </button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-fun bg-warning text-dark px-3 py-2">🏠 Menu Utama</a>
        </div>
    </div>
</nav>

<main class="container mb-5">
    <?php if ($pesanNotif !== ''): ?>
        <div class="alert alert-<?= proteksi($tipeNotif) ?> border-3 border-dark fw-bold mb-4">
            <?= proteksi($pesanNotif) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="bubble-card p-4">
                <h4 class="mb-3">✍️ Input Jarkoman & Jadwal H-2 / H-1 / Hari H</h4>
                <form method="post" enctype="multipart/form-data" id="formJarkoman">
                    <input type="hidden" name="csrf_token" value="<?= proteksi((string)$_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="aksi" value="buat_jadwal_jarkoman">

                    <div class="mb-3">
                        <label class="form-label fw-bold">1. Jenis Jarkoman</label>
                        <select name="jenis_jarkoman" id="jenisJarkoman" class="form-select" onchange="toggleFormMode()">
                            <option value="masuk_pengajaran">📚 Masuk Pengajaran</option>
                            <option value="libur_srengseng">🏖️ Libur Anak Srengseng</option>
                        </select>
                    </div>

                    <div class="mb-3" id="wrapTargetGrup">
                        <label class="form-label fw-bold">2. Target Grup</label>
                        <select name="target_grup" id="targetGrup" class="form-select" onchange="toggleJamKumpul()">
                            <option value="superstar_kabinet">🌟 Superstar & Kabinet</option>
                            <option value="anak_srengseng">🧸 Anak Srengseng (Otomatis Kirim Poster)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-bold" id="labelTanggalAcara">3. Tanggal Pengajaran</label>
                            <input type="date" name="tanggal_acara" id="tanggalAcara" class="form-control" value="<?= proteksi(date('Y-m-d', strtotime('next sunday'))) ?>" required>
                        </div>
                        <div class="col-12 col-sm-6" id="wrapTanggalKembali" style="display:none;">
                            <label class="form-label fw-bold">Tanggal Masuk Kembali</label>
                            <input type="date" name="tanggal_masuk_kembali" id="tanggalKembali" class="form-control" value="<?= proteksi(date('Y-m-d', strtotime('+2 weeks sunday'))) ?>">
                        </div>
                        <div class="col-12 col-sm-6" id="wrapTempat">
                            <label class="form-label fw-bold">4. Tempat</label>
                            <input type="text" name="tempat" id="tempatInput" class="form-control" value="Hutan Kota Srengseng" required>
                        </div>
                    </div>

                    <div id="wrapAlasanLibur" class="mb-3" style="display:none;">
                        <label class="form-label fw-bold">Keterangan / Alasan Libur</label>
                        <input type="text" name="alasan_libur" id="alasanLibur" class="form-control" value="ujian sekolah" placeholder="Contoh: ujian sekolah">
                    </div>

                    <div id="wrapDetailMasuk">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kegiatan</label>
                            <input type="text" name="kegiatan" id="kegiatanInput" class="form-control" value="Kegiatan Les">
                        </div>

                        <div class="row g-2 mb-3" id="wrapJamKumpulBerangkat">
                            <div class="col-6">
                                <label class="form-label fw-bold">Jam Kumpul</label>
                                <input type="text" name="jam_kumpul" id="jamKumpul" class="form-control" value="07:30 - 08:00">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Jam Berangkat</label>
                                <input type="text" name="jam_berangkat" id="jamBerangkat" class="form-control" value="08:00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Jam Pengajaran</label>
                            <input type="text" name="jam_pengajaran" id="jamPengajaran" class="form-control" value="09:00 - Selesai">
                        </div>
                    </div>

                    <div class="mb-3" id="wrapUploadSrengseng" style="display:none;">
                        <label class="form-label fw-bold">Ganti Poster Srengseng (Opsional)</label>
                        <input type="file" name="gambar_jarkoman" id="inputGambarSrengseng" class="form-control" accept="image/*">
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($punyaAksesKirim): ?>
                            <button type="submit" class="btn btn-fun text-white px-4 py-2 flex-fill" style="background-color:#ff006e;">
                                💾 Simpan & Jadwalkan H-2, H-1, Hari H
                            </button>
                            <button type="button" onclick="bukaWaLangsung()" class="btn btn-fun text-white px-4 py-2" style="background-color:#06d6a0;">
                                💬 Kirim ke WA Sekarang
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-fun text-white px-4 py-2 flex-fill" style="background-color:#ff006e;" data-bs-toggle="modal" data-bs-target="#modalAuthJarkoman">
                                🔐 Login untuk Simpan Jadwal
                            </button>
                            <button type="button" class="btn btn-fun text-white px-4 py-2" style="background-color:#06d6a0;" data-bs-toggle="modal" data-bs-target="#modalAuthJarkoman">
                                🔐 Login untuk Kirim WA
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="bubble-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="m-0">👀 Preview Jarkoman</h5>
                        <div class="d-flex gap-2">
                            <a id="btnUnduhPoster" href="#" download class="btn btn-sm btn-fun bg-warning text-dark px-3 py-1" style="display:none;">
                                ⬇️ Unduh Poster
                            </a>
                            <button type="button" onclick="salinPreview()" class="btn btn-sm btn-fun bg-info text-dark px-3 py-1">
                                📋 Salin Teks
                            </button>
                        </div>
                    </div>
                    <div id="wrapFotoPreview" class="mb-3 text-center" style="display:none;">
                        <img id="imgPreviewSrengseng" src="" alt="Poster Anak Srengseng" class="poster-preview-img img-fluid">
                    </div>
                    <div id="previewTeks" class="preview-box mb-3"></div>
                </div>
                <div class="small text-muted fw-bold">
                    Poster Anak Srengseng otomatis terunduh saat klik <b>Kirim ke WA Sekarang</b> / <b>Buka WA</b> agar langsung bisa dilampirkan di grup WA.
                </div>
            </div>
        </div>
    </div>

    <div class="bubble-card p-4 mt-4">
        <h4 class="mb-3">🗓️ Daftar Jadwal Jarkoman (H-2, H-1, Hari H)</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Tahap</th>
                        <th>Tgl Kirim</th>
                        <th>Target & Jenis</th>
                        <th>Status</th>
                        <th>Aksi Kirim WA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarJadwal)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted fw-bold">Belum ada jadwal jarkoman tersimpan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($daftarJadwal as $row): ?>
                            <?php
                                $tglKirim = (string)$row['tanggal_kirim'];
                                $isDue = ($tglKirim <= $hariIni);
                                $linkWaRow = buatLinkWa((string)$row['pesan_teks']);
                                $isRowSrengseng = ($row['jenis_jarkoman'] === 'libur_srengseng' || $row['target_grup'] === 'anak_srengseng');
                                $posterRowUrl = '';
                                $posterRowNama = '';
                                if ($isRowSrengseng) {
                                    $namaFileRow = (string)($row['gambar_path'] ?? '');
                                    if ($namaFileRow !== '' && is_file(__DIR__ . '/uploads/jarkoman/' . $namaFileRow)) {
                                        $posterRowUrl = $baseUrl . '/uploads/jarkoman/' . rawurlencode($namaFileRow);
                                        $posterRowNama = $namaFileRow;
                                    } elseif ($namaFileRow !== '' && is_file(__DIR__ . '/' . $namaFileRow)) {
                                        $posterRowUrl = $baseUrl . '/' . rawurlencode($namaFileRow);
                                        $posterRowNama = $namaFileRow;
                                    } else {
                                        $cekPoster = cariFilePosterSrengseng((string)$row['jenis_jarkoman'], $baseUrl);
                                        $posterRowUrl = $cekPoster['url'];
                                        $posterRowNama = $cekPoster['nama_file'];
                                    }
                                }
                            ?>
                            <tr>
                                <td><span class="badge bg-dark"><?= proteksi((string)$row['hari_ke']) ?></span></td>
                                <td class="fw-bold"><?= proteksi(date('d-m-Y', strtotime($tglKirim))) ?></td>
                                <td>
                                    <div class="fw-bold">
                                        <?= $row['jenis_jarkoman'] === 'libur_srengseng' ? '🏖️ Libur Srengseng' : ($row['target_grup'] === 'anak_srengseng' ? '🧸 Anak Srengseng' : '🌟 Superstar & Kabinet') ?>
                                    </div>
                                    <small class="text-muted">Acara: <?= proteksi(date('d-m-Y', strtotime((string)$row['tanggal_acara']))) ?></small>
                                </td>
                                <td>
                                    <?php if ($row['status_kirim'] === 'terkirim'): ?>
                                        <span class="badge bg-success">✅ Terkirim</span>
                                    <?php elseif ($isDue): ?>
                                        <span class="badge bg-warning text-dark">🔔 Waktunya Kirim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">⏳ Menunggu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($punyaAksesKirim): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <a href="<?= proteksi($linkWaRow) ?>"
                                               target="_blank"
                                               rel="noopener"
                                               onclick="<?= $posterRowUrl !== '' ? 'unduhPosterUrl(' . proteksi(json_encode($posterRowUrl, JSON_UNESCAPED_SLASHES)) . ', ' . proteksi(json_encode($posterRowNama, JSON_UNESCAPED_SLASHES)) . ');' : '' ?>"
                                               class="btn btn-sm btn-fun text-white px-2 py-1"
                                               style="background-color:#06d6a0;">
                                                💬 Buka WA
                                            </a>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= proteksi((string)$_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="aksi" value="tandai_terkirim">
                                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-dark fw-bold">Tandai Terkirim</button>
                                            </form>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= proteksi((string)$_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="aksi" value="hapus_jadwal">
                                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger fw-bold">🗑️</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalAuthJarkoman">
                                            🔐 Login Kirim
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
</main>

<!-- MODAL LOGIN JARKOMAN -->
<div class="modal fade" id="modalAuthJarkoman" tabindex="-1" aria-labelledby="modalAuthJarkomanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bubble-card p-2">
            <div class="modal-header border-bottom border-3 border-dark">
                <h5 class="modal-title" id="modalAuthJarkomanLabel">🔐 Login Akses Kirim Jarkoman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= proteksi((string)$_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="aksi" value="login_jarkoman">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" required autocomplete="username">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer border-top border-3 border-dark">
                    <button type="button" class="btn btn-outline-dark fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-fun text-white px-4 py-2" style="background-color:#06d6a0;">🔓 Masuk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($bukaModalAuth): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('modalAuthJarkoman');
    if (el) new bootstrap.Modal(el).show();
});
</script>
<?php endif; ?>

<script>
const POSTER_MASUK = <?= json_encode($posterMasuk, JSON_UNESCAPED_SLASHES) ?>;
const POSTER_LIBUR = <?= json_encode($posterLibur, JSON_UNESCAPED_SLASHES) ?>;

const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function formatTglJs(isoStr) {
    if (!isoStr) return '-';
    const parts = isoStr.split('-');
    if (parts.length !== 3) return isoStr;
    const dt = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return `${namaHari[dt.getDay()]}, ${String(dt.getDate()).padStart(2, '0')} ${namaBulan[dt.getMonth()]} ${dt.getFullYear()}`;
}

function toggleFormMode() {
    const jenis = document.getElementById('jenisJarkoman').value;
    const isLibur = (jenis === 'libur_srengseng');

    document.getElementById('wrapTargetGrup').style.display = isLibur ? 'none' : 'block';
    document.getElementById('wrapDetailMasuk').style.display = isLibur ? 'none' : 'block';
    document.getElementById('wrapTempat').style.display = isLibur ? 'none' : 'block';
    document.getElementById('wrapTanggalKembali').style.display = isLibur ? 'block' : 'none';
    document.getElementById('wrapAlasanLibur').style.display = isLibur ? 'block' : 'none';
    document.getElementById('labelTanggalAcara').textContent = isLibur ? '3. Tanggal Mulai Libur' : '3. Tanggal Pengajaran';

    if (isLibur) {
        document.getElementById('targetGrup').value = 'anak_srengseng';
    }
    toggleJamKumpul();
}

function toggleJamKumpul() {
    const jenis = document.getElementById('jenisJarkoman').value;
    const grup = document.getElementById('targetGrup').value;
    const isSrengseng = (jenis === 'libur_srengseng' || grup === 'anak_srengseng');
    const showKumpul = (!isSrengseng && jenis === 'masuk_pengajaran');

    document.getElementById('wrapJamKumpulBerangkat').style.display = showKumpul ? 'flex' : 'none';
    document.getElementById('wrapUploadSrengseng').style.display = isSrengseng ? 'block' : 'none';
    if (!isSrengseng) {
        const fileEl = document.getElementById('inputGambarSrengseng');
        if (fileEl) fileEl.value = '';
    }
    updatePreview();
}

function susunTeksPreview() {
    const jenis = document.getElementById('jenisJarkoman').value;
    const grup = jenis === 'libur_srengseng' ? 'anak_srengseng' : document.getElementById('targetGrup').value;
    const tglAcara = formatTglJs(document.getElementById('tanggalAcara').value);
    const tglKembali = formatTglJs(document.getElementById('tanggalKembali').value);
    const alasan = document.getElementById('alasanLibur').value || 'ujian sekolah';
    const tempat = document.getElementById('tempatInput').value || 'Hutan Kota Srengseng';
    const kegiatan = document.getElementById('kegiatanInput').value || 'Kegiatan Les';
    const kumpul = document.getElementById('jamKumpul').value || '07:30 - 08:00';
    const berangkat = document.getElementById('jamBerangkat').value || '08:00';
    const pengajaran = document.getElementById('jamPengajaran').value || '09:00 - Selesai';

    if (jenis === 'libur_srengseng') {
        return `Halo Adik-adik! Ada pengumuman penting, nih.\n\nUntuk sementara waktu, kegiatan les kita libur dulu yaa. Soalnya, kakak abang pengajar lagi mau ${alasan} dulu nih, doakan lancar ya!\n\nTapi tenang aja, kita bakal belajar dan seru-seruan lagi pada hari ${tglKembali}.\n\nJangan lupa datang lagi di tanggal itu ya, Adik-adik! Sampai jumpa lagi dan tetap semangat!`;
    }

    if (grup === 'anak_srengseng') {
        return `Assalamualaikum wr.wb.,\n\nBerhubungan dengan kegiatan Les yang diadakan setiap satu kali pertemuan dalam satu Minggu. Kegiatan Les akan kembali dilaksanakan pada :\n🗓️ : ${tglAcara}\n🏫 : ${tempat}\n📌 : ${kegiatan}\n🕒 : ${pengajaran}\n\nDiharapkan Adik-adik dapat datang untuk mengikuti kegiatan Les ini. Terimakasih atas perhatiannya. 🙏\n\nWassalamualaikum wr.wb`;
    }

    return `Assalamualaikum Wr. Wb.,\n\nBerhubungan dengan kegiatan pengajaran yang diadakan setiap seminggu. Maka dari itu, kami mengundang dan mengajak Abang, Kakak dan Teman-Teman untuk mengikuti kegiatan ini yang akan kembali dilaksanakan pada :\n🗓️ : ${tglAcara}\n🏫 : ${tempat}\n📌 : ${kegiatan}\n🕒\nKumpul : ${kumpul}\nBerangkat : ${berangkat}\nPengajaran : ${pengajaran}\n\nKami mengharapkan kehadiran Abang, Kakak dan Teman-Teman di kegiatan ini.\nTerimakasih atas perhatiannya.🙏\n\nWassalamualaikum Wr. Wb.`;
}

function getPosterAktif() {
    const jenis = document.getElementById('jenisJarkoman').value;
    const grup = jenis === 'libur_srengseng' ? 'anak_srengseng' : document.getElementById('targetGrup').value;
    const isSrengseng = (jenis === 'libur_srengseng' || grup === 'anak_srengseng');
    if (!isSrengseng) {
        return { url: '', namaFile: '' };
    }
    const fileInput = document.getElementById('inputGambarSrengseng');
    if (fileInput && fileInput.files && fileInput.files[0]) {
        return {
            url: URL.createObjectURL(fileInput.files[0]),
            namaFile: fileInput.files[0].name
        };
    }
    const info = (jenis === 'libur_srengseng') ? POSTER_LIBUR : POSTER_MASUK;
    return {
        url: info.url || '',
        namaFile: info.nama_file || 'poster-srengseng.png'
    };
}

function unduhPosterUrl(urlPoster, namaFile = 'poster-srengseng.png') {
    if (!urlPoster) return;
    const link = document.createElement('a');
    link.href = urlPoster;
    link.download = namaFile || 'poster-srengseng.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function updatePreview() {
    const jenis = document.getElementById('jenisJarkoman').value;
    const grup = jenis === 'libur_srengseng' ? 'anak_srengseng' : document.getElementById('targetGrup').value;
    const isSrengseng = (jenis === 'libur_srengseng' || grup === 'anak_srengseng');

    const wrapFoto = document.getElementById('wrapFotoPreview');
    const imgFoto = document.getElementById('imgPreviewSrengseng');
    const btnUnduh = document.getElementById('btnUnduhPoster');

    if (isSrengseng) {
        const poster = getPosterAktif();
        if (poster.url) {
            wrapFoto.style.display = 'block';
            imgFoto.onerror = function () {
                wrapFoto.style.display = 'none';
                btnUnduh.style.display = 'none';
            };
            imgFoto.onload = function () {
                wrapFoto.style.display = 'block';
                btnUnduh.style.display = 'inline-flex';
                btnUnduh.href = poster.url;
                btnUnduh.setAttribute('download', poster.namaFile);
            };
            imgFoto.src = poster.url;
        } else {
            wrapFoto.style.display = 'none';
            imgFoto.src = '';
            btnUnduh.style.display = 'none';
        }
    } else {
        wrapFoto.style.display = 'none';
        imgFoto.src = '';
        btnUnduh.style.display = 'none';
    }

    document.getElementById('previewTeks').textContent = susunTeksPreview();
}

function salinPreview() {
    const teks = susunTeksPreview();
    navigator.clipboard.writeText(teks).then(() => {
        alert('Teks jarkoman berhasil disalin!');
    });
}

function bukaWaLangsung() {
    const poster = getPosterAktif();
    if (poster.url) {
        unduhPosterUrl(poster.url, poster.namaFile);
    }
    const encoded = encodeURIComponent(susunTeksPreview());
    window.open(`https://wa.me/?text=${encoded}`, '_blank', 'noopener');
}

document.querySelectorAll('#formJarkoman input, #formJarkoman select').forEach(el => {
    el.addEventListener('input', updatePreview);
    el.addEventListener('change', updatePreview);
});

toggleFormMode();
</script>
</body>
</html>