<?php
// Setting koneksi DB - sesuaikan dengan punyamu
define('DB_HOST', 'localhost');
define('DB_NAME', 'scam_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');

define('DISCORD_BOT_TOKEN', 'TOKEN');
define('STEAM_API_KEY',     'TOKEN');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]);
    }
    return $pdo;
}
