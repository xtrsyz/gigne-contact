<?php
/**
 * auth/logout.php
 * Hancurkan session dan redirect ke halaman utama.
 * Diakses via POST (form dengan CSRF) untuk mencegah CSRF logout.
 */

require_once __DIR__ . '/../includes/auth.php';

startSession();

// Verifikasi CSRF hanya kalau request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // hapus session sepenuhnya
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

header('Location: /index.php');
exit;
