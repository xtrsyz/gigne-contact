<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$keyword  = trim($_GET['q'] ?? '');
$networks = $keyword !== '' ? searchTags($keyword, true) : listRecent(30, true);

renderHeader('Tracking Data Penipuan');
?>
    <h1>Tracking Data Penipuan</h1>

    <form class="search" method="get">
        <input type="text" name="q" value="<?= e($keyword) ?>"
               placeholder="Cari no rekening / Discord ID / nama / tag / no HP...">
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
            <a class="detail" href="/<?= detailUrl($first) ?>">
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
<?php renderFooter(); ?>
