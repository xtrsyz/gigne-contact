<?php
/**
 * Auth helpers — session, current user, CSRF, require-guards.
 *
 * Di-include oleh halaman yang butuh autentikasi / CSRF protection.
 * Selalu require config/auth.php sebelum memanggil fungsi di sini.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

/* =========================================================================
 * SESSION
 * ========================================================================= */

/** Mulai session dengan aman (cek belum aktif) */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/* =========================================================================
 * CURRENT USER
 * ========================================================================= */

/** Ambil data user yang sedang login dari session, atau null kalau belum login */
function currentUser(): ?array
{
    startSession();
    if (empty($_SESSION['user_id'])) return null;

    // cache di session supaya tidak query DB terus
    if (!empty($_SESSION['user_data'])) return $_SESSION['user_data'];

    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    if ($user) {
        $_SESSION['user_data'] = $user;
    }
    return $user;
}

/** True kalau user sudah login */
function isLoggedIn(): bool
{
    return currentUser() !== null;
}

/** True kalau email user ada di daftar APP_ADMINS */
function isAdmin(): bool
{
    $user = currentUser();
    if (!$user) return false;
    return in_array($user['email'], APP_ADMINS, true);
}

/* =========================================================================
 * REQUIRE GUARDS
 * ========================================================================= */

/**
 * Arahkan ke login kalau belum login.
 * Simpan URL tujuan supaya bisa redirect balik setelah login.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        startSession();
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /auth/login');
        exit;
    }
}

/** Tampilkan 403 kalau bukan admin */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>403 Forbidden</title></head><body>';
        echo '<h1>403 — Akses Ditolak</h1>';
        echo '<p>Halaman ini hanya untuk admin.</p>';
        echo '<a href="/">Kembali ke beranda</a>';
        echo '</body></html>';
        exit;
    }
}

/* =========================================================================
 * CSRF
 * ========================================================================= */

/** Dapatkan (atau buat) CSRF token untuk session ini */
function csrfToken(): string
{
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token dari POST.
 * Lempar RuntimeException kalau tidak valid.
 */
function verifyCsrf(): void
{
    startSession();
    $token = $_POST['csrf_token'] ?? '';
    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        throw new RuntimeException('Token CSRF tidak valid. Coba muat ulang halaman dan submit kembali.');
    }
}
