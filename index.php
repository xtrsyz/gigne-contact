<?php
require_once __DIR__ . '/includes/functions.php';

$keyword  = trim($_GET['q'] ?? '');
$networks = $keyword !== '' ? searchTags($keyword) : listRecent();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tracking Data Penipuan</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        nav a { margin-right: 1rem; }
        .search { display: flex; gap: .5rem; margin: 1rem 0; }
        .search input { flex: 1; padding: .6rem; }
        .search button { padding: .6rem 1.2rem; cursor: pointer; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .badge { display: inline-block; background: #eef; border: 1px solid #dde; border-radius: 4px; padding: .2rem .5rem; font-size: .85rem; margin: .15rem; }
        .tag-label { display: inline-block; background: #fde8e8; color: #b91c1c; border-radius: 4px; padding: .1rem .5rem; font-size: .8rem; }
        .meta { color: #666; font-size: .9rem; }
        a.detail { text-decoration: none; color: inherit; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php">Cari</a>
        <a href="add.php">Input Data</a>
    </nav>
    <h1>Tracking Data Penipuan</h1>

    <form class="search" method="get">
        <input type="text" name="q" value="<?= e($keyword) ?>"
               placeholder="Cari no rekening / Discord ID / nama / tag...">
        <button type="submit">Cari</button>
    </form>

    <?php if ($keyword !== ''): ?>
        <p class="meta">Hasil untuk "<strong><?= e($keyword) ?></strong>": <?= count($networks) ?> jaringan ditemukan</p>
    <?php else: ?>
        <h2>Data Terbaru</h2>
    <?php endif; ?>

    <?php if (empty($networks)): ?>
        <p>Belum ada data / tidak ditemukan.</p>
    <?php endif; ?>

    <?php foreach ($networks as $net):
        $name = '';
        $tagLabel = '';
        foreach ($net as $n) {
            if (!$name && $n['name']) $name = $n['name'];
            if (!$tagLabel && $n['tag']) $tagLabel = $n['tag'];
        }
        $first = $net[0]['identifier'] ?? '';
    ?>
        <div class="card">
            <a class="detail" href="detail.php?id=<?= urlencode($first) ?>">
                <h3>
                    <?= e($name ?: 'Tanpa nama') ?>
                    <?php if ($tagLabel): ?><span class="tag-label"><?= e($tagLabel) ?></span><?php endif; ?>
                </h3>
                <p class="meta"><?= count($net) ?> identitas terhubung</p>
                <div>
                    <?php foreach ($net as $n):
                        [$type, $acc] = parseIdentifier($n['identifier']); ?>
                        <span class="badge"><?= e(bankName($type)) ?>: <strong><?= e($acc) ?></strong></span>
                    <?php endforeach; ?>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</body>
</html>
