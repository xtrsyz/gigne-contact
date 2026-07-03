<?php
/**
 * Konfigurasi Google OAuth 2.0 — salin file ini ke config/auth.php
 * dan isi dengan nilai asli dari Google Cloud Console.
 *
 * JANGAN commit config/auth.php ke repo (sudah masuk .gitignore).
 *
 * Cara mendapatkan kredensial:
 *  1. Buka https://console.cloud.google.com/
 *  2. Buat project baru (atau gunakan yang sudah ada).
 *  3. APIs & Services → OAuth consent screen → External, isi info aplikasi.
 *  4. APIs & Services → Credentials → Create Credentials → OAuth client ID
 *     - Application type: Web application
 *     - Authorized redirect URIs: tambahkan GOOGLE_REDIRECT_URI di bawah.
 *  5. Salin Client ID & Client Secret ke konstanta di bawah.
 */

// Client ID dari Google Cloud Console
define('GOOGLE_CLIENT_ID', '');

// Client Secret dari Google Cloud Console
define('GOOGLE_CLIENT_SECRET', '');

// URI callback yang HARUS sama persis dengan yang didaftarkan di Google Console
// Contoh untuk development lokal:
define('GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth/callback.php');

/**
 * Daftar email yang memiliki akses panel admin.
 * Tambahkan email Google akun admin di sini.
 *
 * Contoh: ['admin@example.com', 'moderator@example.com']
 */
define('APP_ADMINS', [
    // 'admin@example.com',
]);
