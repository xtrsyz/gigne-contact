<?php
require_once __DIR__ . '/includes/functions.php';

$rawId = trim($_GET['id'] ?? '');

// normalisasi kalau yang dibuka berupa identifier phone
// (biar buka detail via 08xxx atau 628xxx sama-sama nemu)
if ($rawId !== '') {
    [$t, $v]    = parseIdentifier($rawId);
    $identifier = makeIdentifier($t, $v);
} else {
    $identifier = '';
}

$network = $identifier !== '' ? getNetwork($identifier) : [];

if (empty($network)) {
    http_response_code(404);
    echo 'Data tidak ditemukan.';
    exit;
}

$root = findRoot($identifier);

// ambil nama & tag representatif
$name = $tagLabel = '';
foreach ($network as $n) {
    if (!$name && $n['name']) $name = $n['name'];
    if (!$tagLabel && $n['tag']) $tagLabel = $n['tag'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail - <?= e($name ?: $identifier) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        nav a { margin-right: 1rem; }
        .section { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { text-align: left; padding: .45rem .5rem; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .tag-label { display: inline-block; background: #fde8e8; color: #b91c1c; border-radius: 4px; padding: .15rem .6rem; }
        .meta { color: #666; font-size: .9rem; }
        .root { color: #1a7f1a; font-weight: 600; }
        code { background: #f4f4f4; padding: .1rem .35rem; border-radius: 3px; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php">Cari</a>
        <a href="add.php">Input Data</a>
    </nav>

    <h1><?= e($name ?: 'Tanpa nama') ?>
        <?php if ($tagLabel): ?><span class="tag-label"><?= e($tagLabel) ?></span><?php endif; ?>
    </h1>
    <p class="meta"><?= count($network) ?> identitas terhubung - root: <code><?= e($root) ?></code></p>

    <div class="section">
        <h2>Semua Identitas Terhubung</h2>
        <table>
            <tr><th>Type Data</th><th>ID Akun</th><th>Nama</th><th>Terhubung ke</th><th>Waktu</th></tr>
            <?php foreach ($network as $n):
                [$type, $acc] = parseIdentifier($n['identifier']);
                $isRoot = ($n['identifier'] === $root);
            ?>
                <tr>
                    <td><?= e(bankName($type)) ?></td>
                    <td>
                        <strong><?= e($acc) ?></strong>
                        <?php if ($isRoot): ?><span class="root"> * root</span><?php endif; ?>
                    </td>
                    <td><?= e($n['name']) ?></td>
                    <td><code><?= e($n['id_link'] ?: '-') ?></code></td>
                    <td class="meta"><?= e($n['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <h2>Link Bukti</h2>
        <?php
        $links = [];
        foreach ($network as $n) {
            if (!empty($n['url'])) $links[$n['url']] = $n['identifier'];
        }
        ?>
        <?php if (empty($links)): ?>
            <p class="meta">Belum ada link bukti.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($links as $url => $fromIdent): ?>
                    <li>
                        <a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($url) ?></a>
                        <span class="meta">(dari <?= e($fromIdent) ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
