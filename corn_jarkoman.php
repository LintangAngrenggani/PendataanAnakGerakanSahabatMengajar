<?php
declare(strict_types=1);
require_once __DIR__ . '/koneksi.php';

// Jika dipanggil lewat HTTP/Cron URL, samakan token ini dengan query ?key=...
$tokenRahasia = 'GANTI_TOKEN_RAHASIA';
if (PHP_SAPI !== 'cli' && (string)($_GET['key'] ?? '') !== $tokenRahasia) {
    http_response_code(403);
    exit('Akses ditolak.');
}

// Isi konfigurasi API WA jika ingin kirim otomatis via gateway (misal Fonnte / WA Cloud API)
$waApiToken = '';
$waTargetId = ''; // Contoh: ID grup / nomor tujuan gateway jika didukung provider

$hariIni = date('Y-m-d');
$stmt = $pdo->prepare('SELECT * FROM jarkoman_schedules WHERE status_kirim = "menunggu" AND tanggal_kirim <= ? ORDER BY id ASC');
$stmt->execute([$hariIni]);
$antrean = $stmt->fetchAll();

foreach ($antrean as $item) {
    $id = (int)$item['id'];
    $pesan = (string)$item['pesan_teks'];
    $gambarUrl = !empty($item['gambar_path'])
        ? 'https://gsmconnect.site/uploads/jarkoman/' . rawurlencode((string)$item['gambar_path'])
        : '';

    $terkirim = false;
    if ($waApiToken !== '' && $waTargetId !== '') {
        $payload = [
            'target'  => $waTargetId,
            'message' => $pesan,
        ];
        if ($gambarUrl !== '') {
            $payload['url'] = $gambarUrl;
        }
        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $waApiToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $resp = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $terkirim = ($resp !== false && $httpCode >= 200 && $httpCode < 300);
    }

    if ($terkirim) {
        $upd = $pdo->prepare('UPDATE jarkoman_schedules SET status_kirim = "terkirim" WHERE id = ?');
        $upd->execute([$id]);
    }
}

echo "Proses cron jarkoman selesai (" . count($antrean) . " jadwal diproses).\n";