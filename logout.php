<?php
declare(strict_types=1);
session_start();

unset($_SESSION['is_admin_logged_in']);
header('Location: index.php');
exit;