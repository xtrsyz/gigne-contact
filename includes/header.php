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
        body { font-family: system-ui, sans-serif; max-width: 960px; margin: 0 auto; padding: 0 1rem 3rem; }
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

        /* ─────────────── TABEL RESPONSIVE (card di mobile) ─────────────── */
        .btn-group { display: flex; flex-wrap: wrap; gap: .5rem; }
        @media (max-width: 640px) {
            .responsive-table thead { display: none; }
            .responsive-table, .responsive-table tbody,
            .responsive-table tr, .responsive-table td { display: block; width: 100%; }

            .responsive-table tr {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: .5rem .75rem;
                margin-bottom: .75rem;
            }
            .responsive-table td {
                border: none;
                padding: .35rem 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
            }
            .responsive-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #666;
                flex-shrink: 0;
            }
            .responsive-table td:not([data-label]) { padding: 0; }
            .responsive-table td:empty::before { content: ""; }

            .btn-group .btn { flex: 1; text-align: center; }
        }

       /* ─────────────── DARK MODE (auto, ikut setelan OS) ─────────────── */
        @media (prefers-color-scheme: dark) {
            body { background: #121212; color: #e3e3e3; }

            /* nav */
            nav { border-bottom-color: #333; }
            nav a { color: #4ade80; }
            nav .user-info strong { color: #e3e3e3; }

            /* banner disclaimer */
            .banner { background: #2a2410; border-color: #6b5e1a; }
            .banner strong { color: #fbbf24; }

            /* form controls */
            input, select, textarea {
                background: #1e1e1e; color: #e3e3e3; border-color: #444;
            }
            input::placeholder, textarea::placeholder { color: #888; }
            input[readonly] { background: #2a2a2a !important; color: #aaa; }
            label { color: #e3e3e3; }

            /* buttons */
            button, .btn { background: #2a2a2a; color: #e3e3e3; border-color: #555; }
            .btn-primary { background: #16a34a; color: #fff; border-color: #16a34a; }
            .btn-danger  { background: #dc2626; color: #fff; border-color: #dc2626; }

            /* alerts */
            .ok   { background: #12331b; color: #4ade80; }
            .err  { background: #3a1414; color: #f87171; }
            .info { background: #12233f; color: #60a5fa; }

            /* cards & sections */
            .card, .section { background: #1a1a1a; border-color: #333; }
            .badge { background: #1e2140; border-color: #33375e; color: #c7d2fe; }
            .tag-label { background: #3a1414; color: #f87171; }
            .disclaimer-check { background: #2a2410; border-color: #6b5e1a; }

            /* text & meta */
            .meta, small { color: #999; }

            /* tables */
            td, th { border-bottom-color: #2a2a2a; }
            th { background: #1e1e1e; }
            code { background: #2a2a2a; color: #e3e3e3; }

            /* links & status */
            a { color: #60a5fa; }
            .root, .status-active { color: #4ade80; }
            .status-hidden  { color: #fbbf24; }
            .status-removed { color: #f87171; }

            /* avatar profile (discord/steam) */
            .avyimg { border: 2px solid #333; }

            /* card tabel responsive di dark mode */
            .responsive-table tr { border-color: #333 !important; }
            .responsive-table td::before { color: #999 !important; }

            /* ─────────────── SELECT2 dark mode ─────────────── */
            /* kotak utama (yang keliatan sebelum diklik) */
            .select2-container--default .select2-selection--single {
                background: #1e1e1e !important;
                border-color: #444 !important;
            }
            .select2-container--default .select2-selection--single
                .select2-selection__rendered { color: #e3e3e3 !important; }
            .select2-container--default .select2-selection--single
                .select2-selection__placeholder { color: #888 !important; }
            .select2-container--default .select2-selection--single
                .select2-selection__arrow b { border-color: #888 transparent transparent transparent !important; }

            /* dropdown yang kebuka */
            .select2-dropdown {
                background: #1e1e1e !important;
                border-color: #444 !important;
            }
            /* kotak search di dalam dropdown */
            .select2-container--default .select2-search--dropdown .select2-search__field {
                background: #2a2a2a !important;
                color: #e3e3e3 !important;
                border-color: #555 !important;
            }
            /* list opsi */
            .select2-container--default .select2-results__option {
                background: #1e1e1e !important;
                color: #e3e3e3 !important;
            }
            /* opsi yang di-hover / lagi disorot */
            .select2-container--default .select2-results__option--highlighted[aria-selected] {
                background: #16a34a !important;
                color: #fff !important;
            }
            /* opsi yang udah kepilih */
            .select2-container--default .select2-results__option[aria-selected="true"] {
                background: #2a2a2a !important;
                color: #4ade80 !important;
            }
        }
    </style>
</head>
<body>
    <nav>
        <a href="/">🔍 Cari</a>
        <a href="/add">✏️ Input Data</a>
        <?php if ($user): ?>
            <a href="/mydata">📋 Data Saya</a>
        <?php endif; ?>
        <?php if ($admin): ?>
            <a href="/admin">⚙️ Panel Admin</a>
        <?php endif; ?>
        <a href="/sanggah">📝 Ajukan Sanggah</a>
        <a href="/disclaimer">⚠️ Disclaimer</a>
        <span class="spacer"></span>
        <?php if ($user): ?>
            <span class="user-info">
                <?php if (!empty($user['picture'])): ?>
                    <img src="<?= e($user['picture']) ?>" alt="foto">
                <?php endif; ?>
                <strong><?= e($user['name'] ?: $user['email']) ?></strong>
                <form method="post" action="/auth/logout" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <button type="submit" style="background:none;border:none;color:#b91c1c;cursor:pointer;font-size:.85rem;padding:0">Keluar</button>
                </form>
            </span>
        <?php else: ?>
            <a href="/auth/login">🔑 Login dengan Google</a>
        <?php endif; ?>
    </nav>

    <div class="banner">
        <strong>⚠️ Disclaimer:</strong>
        Data di sini bersumber dari laporan komunitas dan <strong>belum tentu terverifikasi</strong>.
        Gunakan sebagai referensi awal, bukan vonis akhir.
        Jika data keliru, <a href="/sanggah">ajukan sanggah di sini</a>.
        <a href="/disclaimer">Baca disclaimer lengkap.</a>
    </div>
    <?php
}
