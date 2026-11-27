<?php
declare(strict_types=1);

$DB_HOST = 'sql311.infinityfree.com';    
$DB_NAME = 'if0_42458480_gsm_management'; 
$DB_USER = 'if0_42458480';                
$DB_PASS = 'Gerakan20';           
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $pdoOptions);
    
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Koneksi database gagal: ' . $e->getMessage());
}
