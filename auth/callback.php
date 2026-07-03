<?php
/**
 * auth/callback.php
 * Tukar authorization code dari Google menjadi access token,
 * ambil info user, simpan ke tabel users, lalu set session.
 */

require_once __DIR__ . '/../includes/auth.php';

startSession();

// ── Verifikasi state anti-CSRF ─────────────────────────────────────────────
$state         = $_GET['state'] ?? '';
$sessionState  = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);

if ($state === '' || !hash_equals($sessionState, $state)) {
    http_response_code(400);
    showError('Parameter state tidak valid atau sudah kadaluarsa. Silakan coba login lagi.');
}

// ── Cek error dari Google ──────────────────────────────────────────────────
if (!empty($_GET['error'])) {
    showError('Google menolak login: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));
}

$code = $_GET['code'] ?? '';
if ($code === '') {
    showError('Authorization code tidak ditemukan.');
}

// ── Tukar code → access token via cURL ────────────────────────────────────
$tokenData = curlPost('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (empty($tokenData['access_token'])) {
    showError('Gagal mendapatkan access token dari Google. Coba login ulang.');
}

// ── Ambil info user via userinfo endpoint ──────────────────────────────────
$userInfo = curlGet(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    $tokenData['access_token']
);

if (empty($userInfo['sub']) || empty($userInfo['email'])) {
    showError('Tidak bisa mengambil informasi akun Google Anda.');
}

// Pastikan email sudah terverifikasi Google
if (isset($userInfo['email_verified']) && $userInfo['email_verified'] === false) {
    showError('Email Google Anda belum terverifikasi. Harap verifikasi email terlebih dahulu.');
}

// ── Simpan / update user di DB ─────────────────────────────────────────────
$pdo   = db();
$sub   = $userInfo['sub'];
$email = $userInfo['email'];
$name  = $userInfo['name'] ?? '';
$pic   = $userInfo['picture'] ?? '';

// Cek apakah user sudah ada (by google_sub)
$stmt = $pdo->prepare("SELECT id FROM users WHERE google_sub = ? LIMIT 1");
$stmt->execute([$sub]);
$existing = $stmt->fetchColumn();

if ($existing) {
    // Update info terbaru (nama/foto bisa berubah di Google)
    $pdo->prepare(
        "UPDATE users SET email = ?, name = ?, picture = ? WHERE google_sub = ?"
    )->execute([$email, $name, $pic, $sub]);
    $userId = (int)$existing;
} else {
    // Insert user baru
    $pdo->prepare(
        "INSERT INTO users (google_sub, email, name, picture) VALUES (?, ?, ?, ?)"
    )->execute([$sub, $email, $name, $pic]);
    $userId = (int)$pdo->lastInsertId();
}

// ── Set session ────────────────────────────────────────────────────────────
$_SESSION['user_id']   = $userId;
$_SESSION['user_data'] = [
    'id'         => $userId,
    'google_sub' => $sub,
    'email'      => $email,
    'name'       => $name,
    'picture'    => $pic,
];

// ── Redirect ke halaman asal ───────────────────────────────────────────────
$dest = $_SESSION['redirect_after_login'] ?? '/index.php';
unset($_SESSION['redirect_after_login']);
header('Location: ' . $dest);
exit;

/* =========================================================================
 * HELPERS (lokal, hanya dipakai di file ini)
 * ========================================================================= */

/** Tampilkan pesan error dan stop */
function showError(string $msg): never
{
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    echo '<title>Login Gagal</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:600px;margin:3rem auto;padding:0 1rem}';
    echo '.err{background:#fde8e8;color:#b91c1c;padding:.75rem;border-radius:6px;}</style>';
    echo '</head><body>';
    echo '<h1>Login Gagal</h1>';
    echo '<div class="err">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<p><a href="/auth/login.php">Coba lagi</a> | <a href="/index.php">Kembali ke beranda</a></p>';
    echo '</body></html>';
    exit;
}

/** POST request via cURL, kembalikan array hasil JSON */
function curlPost(string $url, array $fields): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body  = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno || $body === false) return [];
    return json_decode($body, true) ?? [];
}

/** GET request via cURL dengan ****** kembalikan array hasil JSON */
function curlGet(string $url, string $accessToken): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body  = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno || $body === false) return [];
    return json_decode($body, true) ?? [];
}
