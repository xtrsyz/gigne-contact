<?php
/**
 * includes/header.php
 * Partial header bersama: HTML head, nav, banner disclaimer.
 *
 * Cara pakai:
 *   require_once __DIR__ . '/includes/header.php';
 *   renderHeader('Judul Halaman');
 */

require_once __DIR__ . '/auth.php';

function renderHeader(string $title = 'Tracking Data Penipuan'): void
{
    startSession();
    $user    = currentUser();
    $admin   = isAdmin();
    $csrf    = csrfToken();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; max-width: 960px; margin: 0 auto; padding: 0 1rem 3rem; color: #1a1a1a; }
        nav { display: flex; flex-wrap: wrap; gap: .5rem; padding: .75rem 0; border-bottom: 1px solid #ddd; margin-bottom: 1rem; align-items: center; }
        nav a { text-decoration: none; color: #1a7f1a; font-weight: 600; }
        nav a:hover { text-decoration: underline; }
        nav .spacer { flex: 1; }
        nav .user-info { display: flex; align-items: center; gap: .5rem; font-size: .9rem; }
        nav .user-info img { width: 28px; height: 28px; border-radius: 50%; }
        .banner { background: #fff8e1; border: 1px solid #f5c518; border-radius: 6px; padding: .65rem 1rem; font-size: .85rem; margin-bottom: 1.25rem; }
        .banner strong { color: #b45309; }
        label { display: block; margin: .75rem 0 .25rem; font-weight: 600; }
        input, select, textarea { width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 4px; }
        textarea { min-height: 80px; }
        button, .btn { padding: .55rem 1.1rem; cursor: pointer; border-radius: 4px; border: 1px solid #aaa; background: #f5f5f5; font-size: .95rem; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1a7f1a; color: #fff; border-color: #1a7f1a; }
        .btn-danger  { background: #b91c1c; color: #fff; border-color: #b91c1c; }
        .btn-sm { padding: .3rem .7rem; font-size: .82rem; }
        .alert { padding: .75rem; border-radius: 6px; margin: 1rem 0; }
        .ok  { background: #e6f7e6; color: #1a7f1a; }
        .err { background: #fde8e8; color: #b91c1c; }
        .info { background: #e8f0fe; color: #1a56db; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .badge { display: inline-block; background: #eef; border: 1px solid #dde; border-radius: 4px; padding: .2rem .5rem; font-size: .85rem; margin: .15rem; }
        .tag-label { display: inline-block; background: #fde8e8; color: #b91c1c; border-radius: 4px; padding: .1rem .5rem; font-size: .8rem; }
        .meta { color: #666; font-size: .9rem; }
        .section { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { text-align: left; padding: .45rem .5rem; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        th { background: #f9f9f9; }
        .root { color: #1a7f1a; font-weight: 600; }
        code { background: #f4f4f4; padding: .1rem .35rem; border-radius: 3px; font-size: .9em; }
        small { color: #666; font-weight: 400; }
        a.detail { text-decoration: none; color: inherit; }
        .search { display: flex; gap: .5rem; margin: 1rem 0; }
        .search input { flex: 1; padding: .6rem; }
        .search button { padding: .6rem 1.2rem; cursor: pointer; }
        .disclaimer-check { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 1rem; margin: 1rem 0; }
        .status-active  { color: #1a7f1a; }
        .status-hidden  { color: #b45309; }
        .status-removed { color: #b91c1c; text-decoration: line-through; }
    </style>
</head>
<body>
    <nav>
        <a href="/index.php">🔍 Cari</a>
        <a href="/add.php">✏️ Input Data</a>
        <?php if ($user): ?>
            <a href="/mydata.php">📋 Data Saya</a>
        <?php endif; ?>
        <?php if ($admin): ?>
            <a href="/admin.php">⚙️ Panel Admin</a>
        <?php endif; ?>
        <a href="/sanggah.php">📝 Ajukan Sanggah</a>
        <a href="/disclaimer.php">⚠️ Disclaimer</a>
        <span class="spacer"></span>
        <?php if ($user): ?>
            <span class="user-info">
                <?php if (!empty($user['picture'])): ?>
                    <img src="<?= e($user['picture']) ?>" alt="foto">
                <?php endif; ?>
                <strong><?= e($user['name'] ?: $user['email']) ?></strong>
                <form method="post" action="/auth/logout.php" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <button type="submit" style="background:none;border:none;color:#b91c1c;cursor:pointer;font-size:.85rem;padding:0">Keluar</button>
                </form>
            </span>
        <?php else: ?>
            <a href="/auth/login.php">🔑 Login dengan Google</a>
        <?php endif; ?>
    </nav>

    <div class="banner">
        <strong>⚠️ Disclaimer:</strong>
        Data di sini bersumber dari laporan komunitas dan <strong>belum tentu terverifikasi</strong>.
        Gunakan sebagai referensi awal, bukan vonis akhir.
        Jika data keliru, <a href="/sanggah.php">ajukan sanggah di sini</a>.
        <a href="/disclaimer.php">Baca disclaimer lengkap.</a>
    </div>
    <?php
}
