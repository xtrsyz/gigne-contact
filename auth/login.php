<?php
/**
 * auth/login.php
 * Redirect user ke halaman consent Google OAuth 2.0.
 * Menggunakan parameter `state` anti-CSRF yang disimpan di session.
 */

require_once __DIR__ . '/../includes/auth.php';

startSession();

// Kalau sudah login, langsung redirect ke tujuan
if (isLoggedIn()) {
    $dest = $_SESSION['redirect_after_login'] ?? '/index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $dest);
    exit;
}

// Buat state anti-CSRF: random string disimpan di session
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
