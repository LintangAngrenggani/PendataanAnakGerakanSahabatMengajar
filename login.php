<?php
declare(strict_types=1);
session_start();

// Password & Username Default (Bisa Anda sesuaikan)
define('ADMIN_USERNAME', 'Presidium');
define('ADMIN_PASSWORD', 'gsmitpln');

$pesanError = '';

// Jika sudah login, langsung lempar ke rapot.php
if (!empty($_SESSION['is_admin_logged_in'])) {
    header('Location: rapot.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['is_admin_logged_in'] = true;
        
        $redirect = $_GET['redirect'] ?? 'rapot.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $pesanError = 'Username atau password admin salah!';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin Rapot - GSMConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f3fbf7; font-family: 'Nunito', sans-serif; }
        .bubble-card { 
            background: #ffffff; 
            border: 3px solid #1e293b; 
            border-radius: 20px; 
            box-shadow: 5px 5px 0px #1e293b; 
        }
        .form-control { 
            border: 2.5px solid #1e293b; 
            border-radius: 10px; 
            font-weight: 700; 
            padding: 10px;
        }
        .btn-login {
            background-color: #ffbe0b;
            color: #1e293b;
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            border: 3px solid #1e293b;
            border-radius: 12px;
            box-shadow: 3px 3px 0px #1e293b;
            transition: all 0.15s ease;
        }
        .btn-login:hover {
            transform: translate(-2px, -2px);
            box-shadow: 5px 5px 0px #1e293b;
            background-color: #ffb703;
        }
        .btn-login:active {
            background-color: #ffbe0b !important;
            transform: scale(0.97);
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
    <div class="container" style="max-width: 420px;">
        <div class="bubble-card p-4 text-center">
            <h3 class="mb-1" style="font-family:'Fredoka'; color:#1e293b;">🔒 Akses Admin Rapot</h3>
            <p class="text-muted fw-bold small mb-4">Masukan kredensial pengajar untuk mengelola rapot</p>

            <?php if ($pesanError !== ''): ?>
                <div class="alert alert-danger border border-dark fw-bold mb-3 small"><?= htmlspecialchars($pesanError) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold small">Username Admin</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukan username..." required autofocus>
                </div>

                <div class="mb-4 text-start">
                    <label class="form-label fw-bold small">Password Admin</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukan password..." required>
                </div>

                <button type="submit" class="btn btn-login w-100 py-2 mb-3">Masuk ke Rapot</button>
                
                <a href="index.php" class="text-decoration-none text-muted fw-bold small">← Kembali ke Menu Utama</a>
            </form>
        </div>
    </div>
</body>
</html>